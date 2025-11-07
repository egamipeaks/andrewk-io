<?php

use App\Enums\InvoiceLineType;

describe('InvoiceLineType Enum', function () {
    it('has correct values', function () {
        expect(InvoiceLineType::Fixed->value)->toBe('fixed');
        expect(InvoiceLineType::Hourly->value)->toBe('hourly');
    });

    it('returns correct labels', function () {
        expect(InvoiceLineType::Fixed->label())->toBe('Fixed Amount');
        expect(InvoiceLineType::Hourly->label())->toBe('Hourly');
    });

    it('returns correct colors', function () {
        expect(InvoiceLineType::Fixed->color())->toBe('success');
        expect(InvoiceLineType::Hourly->color())->toBe('info');
    });

    it('can be cast from string', function () {
        $type = InvoiceLineType::from('fixed');
        expect($type)->toBe(InvoiceLineType::Fixed);

        $type = InvoiceLineType::from('hourly');
        expect($type)->toBe(InvoiceLineType::Hourly);
    });
});
