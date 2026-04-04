<?php
declare(strict_types=1);

namespace App\Exception\Api;

use Symfony\Contracts\Translation\TranslatorInterface;

final class ProductMissingRequiredAttributesException extends AbstractApiException
{
    /**
     * @param list<string> $codes
     */
    public function __construct(
        string $message,
        array $codes
    ) {
        $codes = array_values(array_unique($codes));
        sort($codes);

        parent::__construct(
            message: $message,
            status: 422,
            type: 'product_missing_required_attributes',
            context: [
                'codes' => $codes,
            ],
        );
    }
}
