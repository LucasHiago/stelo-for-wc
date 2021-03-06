<?php

/*
 * This file is part of gpupo/stelo-sdk
 *
 * (c) Gilmar Pupo <g@g1mr.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * For more information, see
 * <http://www.g1mr.com/stelo-sdk/>.
 */

namespace Gpupo\SteloSdk\Order\Cart;

use Gpupo\CommonSdk\Entity\EntityAbstract;
use Gpupo\CommonSdk\Entity\EntityInterface;

class Item extends EntityAbstract implements EntityInterface
{
    public function getSchema()
    {
        return [
            'productName'       => 'string',
            'productQuantity'   => 'number',
            'productPrice'      => 'number',
            'productSku'        => 'string',
        ];
    }
}
