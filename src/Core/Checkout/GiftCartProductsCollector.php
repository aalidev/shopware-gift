<?php declare(strict_types=1);

namespace Swag\CartAddDiscountForProduct\Core\Checkout;

use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\Cast\Array_;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\PercentagePriceCalculator;
use Shopware\Core\Checkout\Cart\Price\Struct\PercentagePriceDefinition;
use Shopware\Core\Checkout\Cart\Rule\LineItemRule;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class GiftCartProductsCollector implements CartProcessorInterface
{
    /**
     * @var PercentagePriceCalculator
     */
    private $calculator;

    public function __construct(PercentagePriceCalculator $calculator)
    {
        $this->calculator = $calculator;
    }

    public function process(CartDataCollection $data, Cart $original, Cart $toCalculate, SalesChannelContext $context, CartBehavior $behavior): void
    {
        $products = $this->findAwesomeProducts($toCalculate);
        $validSalesChannel = $context->getSalesChannel()->getName() == 'Austria' || 
                             $context->getSalesChannel()->getName() == 'Rest of EU' || 
                             $context->getSalesChannel()->getName() == 'Spain' || 
                             $context->getSalesChannel()->getName() == 'Switzerland' ||
                             null;

        // no awesome products found? early return
        if (!$validSalesChannel) {
            return;
        }

        $discountLineItem = $this->createDiscount('FREE_MASK');

        // declare price definition to define how this price is calculated
        $definition = new PercentagePriceDefinition(
            0,
            0,
            new LineItemRule(LineItemRule::OPERATOR_EQ, $products->getKeys())
        );

        $discountLineItem->setPriceDefinition($definition);

        // calculate price
        $discountLineItem->setPrice(
            $this->calculator->calculate($definition->getPercentage(), $products->getPrices(), $context)
        );
        // add discount to new cart
        if($products->count() > 0) {
            $toCalculate->add($discountLineItem);
        }
    }

    private function findAwesomeProducts(Cart $cart): LineItemCollection
    {
        return $cart->getLineItems();
    }

    private function createDiscount(string $name): LineItem
    {
        $discountLineItem = new LineItem($name, 'free_mask', null, 1);

        $discountLineItem->setLabel('Free mask');
        $discountLineItem->setGood(false);
        $discountLineItem->setStackable(false);
        $discountLineItem->setRemovable(false);
        $discountLineItem->setDescription('Handmade Padre azul mounth-nose protection to each order.');

        return $discountLineItem;
    }
}
