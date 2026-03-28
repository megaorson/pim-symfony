<?php
declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Product;

class ProductOutputProcessor extends AbstractProduct implements ProcessorInterface
{
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): mixed {
        if ($data instanceof Product) {
            return $this->transform($data);
        }

        if (is_iterable($data)) {
            $result = [];

            foreach ($data as $product) {
                $result[] = $this->transform($product);
            }

            return $result;
        }

        return $data;
    }
}
