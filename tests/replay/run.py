import concurrent.futures, json, os, pathlib, subprocess, tempfile, time, uuid
root=pathlib.Path(__file__).parent
results=[]
fixture_dir = tempfile.TemporaryDirectory(prefix='saml-replay-fixtures-')
os.environ['SAML_FIXTURE_DIR'] = fixture_dir.name
os.environ['SAML_DB_NAME'] = 'saml_replay_test_' + uuid.uuid4().hex
# Create only a uniquely named disposable database. Never use a WordPress database.
subprocess.run(['php', '-r', "mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); $c = new mysqli(getenv('SAML_DB_HOST') ?: 'localhost', getenv('SAML_DB_USER') ?: 'root', getenv('SAML_DB_PASSWORD') ?: '', '', (int)(getenv('SAML_DB_PORT') ?: 3306), getenv('SAML_DB_SOCKET') ?: null); $c->query('CREATE DATABASE `' . getenv('SAML_DB_NAME') . '`');"], check=True)

def command(fixture, extra=None):
    out=subprocess.check_output(['php',str(root/'claim.php'),fixture],env={**os.environ,**(extra or {})},text=True)
    return out.strip()
def check(name,fixture,expected,error=None,extra=None):
    row=json.loads(command(fixture,extra));row['test']=name;results.append(row)
    assert row['accepted']==expected,(name,row)
    if error is not None: assert row['error']==error,(name,row)
    assert row['suppression_restored'],row
    assert bool(row['cookie']) == expected,row
    assert not row['strict'],row
    print(name+': PASS')
    return row
try:
    command('setup')
    subprocess.run(['php',str(root/'fixtures.php'),'generate'],check=True)
    # Every fixture shares the same assertion ID; reject invalid signatures without poisoning the ID.
    for fixture in ['unsigned','tampered','untrusted-key']:
        row=check('reject '+fixture,fixture,False,'invalid-saml')
        assert row['database_queries']==0,row
    check('reject missing assertion ID','missing-assertion-id',False,'missing-assertion-id')
    check('accept first valid assertion','valid',True)
    check('reject immediate replay','valid',False,'replayed-assertion')
    check('reject same assertion with newly signed changed response','wrong-audience',False,'replayed-assertion')
    check('response-supplied issuer cannot bypass replay namespace','wrong-issuer',False,'replayed-assertion')
    records=json.loads(command('inspect'));assert len(records)==2 and all(r['autoload']=='no' and r['option_value']=='1' for r in records),records
    command('setup')
    check('accept expired assertion once (strict remains off)','expired',True)
    check('reject expired assertion replay','expired',False,'replayed-assertion')
    # Reuse a record with no TTL and a fixed value. No timestamp-based purge exists.
    command('setup')
    check('fail closed on storage failure','valid',False,'assertion-storage-failed',{'SAML_STORAGE_FAIL':'1'})
    check('fail closed on actual database error','valid',False,'assertion-storage-failed',{'SAML_MISSING_TABLE':'1'})
    check('accept after storage recovers','valid',True)
    # Each process has its own PHP state; replay prevention comes from the shared MySQL unique key.
    command('setup')
    start=str(time.time()+2)
    with concurrent.futures.ThreadPoolExecutor(max_workers=12) as pool:
        rows=list(pool.map(lambda _:json.loads(command('valid',{'SAML_PARALLEL_START':start})),range(12)))
    assert sum(r['accepted'] for r in rows)==1,rows
    assert all(r['accepted'] or r['error'] in ['replayed-assertion', 'assertion-storage-failed'] for r in rows),rows
    assert all(bool(r['cookie']) == r['accepted'] and r['suppression_restored'] for r in rows),rows
    results.append({'test':'12 concurrent submissions','accepted':1,'rejected':11});print('12 concurrent submissions: PASS (one accepted)')
    # Subsite database name differs, but both claim in main site options.
    command('setup')
    check('accept first multisite destination','valid',True,extra={'SAML_MULTISITE':'1'})
    check('reject replay on main site','valid',False,'replayed-assertion')
    records=json.loads(command('inspect'));assert len(records)==2,records
    # Existing records in either format must continue to block reuse.
    for key_format in ['legacy', 'v2']:
        command('setup')
        subprocess.run(['php', str(root/'claim.php'), 'seed', key_format], check=True)
        check('reject pre-existing '+key_format+' record', 'valid', False, 'replayed-assertion')
    command('setup')
    check('restore existing suppression', 'valid', True, extra={'SAML_PREVIOUS_SUPPRESSION':'1'})
    command('setup')
    check('restore suppression after adapter exception', 'valid', False, 'adapter-exception', {'SAML_STORAGE_THROW':'1'})
    command('setup')
    check('accept network with main site 9', 'valid', True, extra={'SAML_MULTISITE':'1','SAML_MAIN_SITE':'9'})
    check('reject other destination in same network', 'valid', False, 'replayed-assertion', {'SAML_MULTISITE':'1','SAML_MAIN_SITE':'9'})
    check('independent network has its own namespace', 'valid', True, extra={'SAML_MULTISITE':'1','SAML_MAIN_SITE':'1'})
    command('setup')
    start=str(time.time()+2)
    with concurrent.futures.ThreadPoolExecutor(max_workers=12) as pool:
        rows=list(pool.map(lambda i:json.loads(command('valid',{'SAML_PARALLEL_START':start,'SAML_LEGACY_WORKER':'1' if i % 2 else ''})),range(12)))
    assert sum(r['accepted'] for r in rows)==1,rows
    assert all(r['accepted'] or r['error'] in ['replayed-assertion', 'assertion-storage-failed'] for r in rows),rows
    print('12 mixed legacy/new concurrent submissions: PASS (one accepted)')
    command('setup')
    for idp, assertion in [('https://idp.example.test/a:', 'b'), ('https://idp.example.test/a', ':b'), ('https://other-idp.example.test/', 'b')]:
        row=json.loads(subprocess.check_output(['php',str(root/'claim.php'),'key',idp,assertion],text=True))
        assert row['accepted'],row
    records=json.loads(command('inspect'))
    assert len(records)==6,records
    expected='wpsimplesaml_used_assertion_v2_' + __import__('hashlib').sha256(b'27:https://idp.example.test/a:1:b').hexdigest()
    assert expected in [r['option_name'] for r in records],records
    print('length-prefixed encoding and configured IdP namespaces: PASS')
    print('All replay checks passed against real MySQL; WordPress adapters are stubbed.')
finally:
    command('drop')
    fixture_dir.cleanup()
