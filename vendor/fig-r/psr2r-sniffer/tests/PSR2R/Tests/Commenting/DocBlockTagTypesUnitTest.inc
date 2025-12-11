<?php
/**
 * @a a
 * @b b
 * @package x
 */

namespace PSR2R\Tests\Commenting;

use PSR2R\Base\AbstractBase;

/**
 * Class DocBlockTagTypesUnitTestInc
 *
 * @package PSR2R\Tests\Commenting
 * @a a
 * @subpackage a
 * @global a
 * @category a
 * @static a
 * @void a
 * @overwrite a
 * @type bool
 * @var int
 * @override x
 * @inheritdoc
 * @inheritDoc
 * @TODO x
 * @todo x
 * @api x
 * @author test
 * @copyright test
 * @deprecated x
 * @example x
 * @filesource x
 * @ignore x
 * @internal x
 * @license MIT
 * @link x
 * @method x
 * @mixin x
 * @override x
 * @param x
 * @property x
 * @property-read x
 * @property-write x
 * @return void
 * @see x
 * @since x
 * @source x
 * @throws \Exception
 * @triggers x
 * @uses x
 * @version x
 * @covers x
 * @coversDefaultClass x
 * @expectedException x
 * @expectedExceptionCode x
 * @expectedExceptionMessage x
 * @expectedExceptionMessageRegExp x
 * @coversNothing
 * @dataProvider x
 * @depends x
 * @group x
 * @uses x
 * @codeCoverageIgnore
 * @codeCoverageIgnoreStart
 * @codeCoverageIgnoreEnd
 * @SuppressWarnings(PHPMD)
 */
class DocBlockTagTypesUnitTestInc {
	/**
	 * @param array|null $a
	 * @param int $b
	 * @return bool
	 * @package PSR2R\Tests\Commenting
	 * @a                        a
	 * @subpackage               a
	 * @global a
	 * @category                 a
	 * @static                   a
	 * @void                     a
	 * @overwrite                a
	 * @type bool
	 * @var int
	 * @override                 x
	 * @inheritdoc
	 * @inheritDoc
	 * @TODO                     x
	 * @todo                     x
	 * @api                      x
	 * @author                   test
	 * @copyright                test
	 * @deprecated               x
	 * @example                  x
	 * @filesource               x
	 * @ignore                   x
	 * @internal                 x
	 * @license                  MIT
	 * @link                     x
	 * @method x
	 * @mixin x
	 * @override                 x
	 * @param x
	 * @property x
	 * @property-read x
	 * @property-write x
	 * @return void
	 * @see                      x
	 * @since                    x
	 * @source                   x
	 * @throws \Exception
	 * @triggers                 x
	 * @uses                     x
	 * @version                  x
	 * @covers                   DocBlockTagTypesUnitTestInc::two
	 * @coversDefaultClass       x
	 * @expectedException x
	 * @expectedExceptionCode    x
	 * @expectedExceptionMessage x
	 * @expectedExceptionMessageRegExp  x
	 * @coversNothing
	 * @dataProvider             x
	 * @depends                  x
	 * @group                    x
	 * @uses                     x
	 * @codeCoverageIgnore
	 * @codeCoverageIgnoreStart
	 * @codeCoverageIgnoreEnd
	 * @SuppressWarnings(PHPMD)
	 */
	public function one(array $a = null, $b) {
		return true;
	}

	/** @noinspection MoreThanThreeArgumentsInspection */

	/**
	 * @param array $c
	 * @param int $d
	 * @return bool
	 */
	public function two(array $c, $d) {
		return false;
	}
}
