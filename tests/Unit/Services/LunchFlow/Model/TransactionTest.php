<?php

/*
 * TransactionTest.php
 * Copyright (c) 2025 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tests\Unit\Services\LunchFlow\Model;

use App\Services\LunchFlow\Model\Transaction;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class TransactionTest extends TestCase
{
    /**
     * Test that getDescription prioritizes payee over description.
     * This is the main use case: payee contains merchant name, description contains generic text.
     */
    public function testGetDescriptionPrioritizesPayeeOverDescription(): void
    {
        $transaction = Transaction::fromArray([
            'id'          => 'test-123',
            'accountId'   => 1,
            'amount'      => '100.00',
            'currency'    => 'EUR',
            'date'        => '2025-01-01',
            'description' => 'Platba kartou',
            'merchant'    => 'Supermarket',
            'payee'       => 'T-Mobile',
        ]);

        $this->assertSame('T-Mobile', $transaction->getDescription());
    }

    /**
     * Test that getDescription returns payee when description is empty.
     */
    public function testGetDescriptionReturnsPayeeWhenDescriptionEmpty(): void
    {
        $transaction = Transaction::fromArray([
            'id'          => 'test-123',
            'accountId'   => 1,
            'amount'      => '100.00',
            'currency'    => 'EUR',
            'date'        => '2025-01-01',
            'description' => '',
            'merchant'    => 'Supermarket',
            'payee'       => 'T-Mobile',
        ]);

        $this->assertSame('T-Mobile', $transaction->getDescription());
    }

    /**
     * Test that getDescription returns counterparty_name when payee is empty.
     */
    public function testGetDescriptionReturnsCounterpartyNameWhenPayeeEmpty(): void
    {
        $transaction = Transaction::fromArray([
            'id'               => 'test-123',
            'accountId'        => 1,
            'amount'           => '100.00',
            'currency'         => 'EUR',
            'date'             => '2025-01-01',
            'description'      => 'Platba kartou',
            'payee'            => '',
            'counterparty_name' => 'John Doe',
            'merchant'         => 'Supermarket',
        ]);

        $this->assertSame('John Doe', $transaction->getDescription());
    }

    /**
     * Test that getDescription returns description only when payee and counterparty_name are empty.
     */
    public function testGetDescriptionReturnsDescriptionAsFallback(): void
    {
        $transaction = Transaction::fromArray([
            'id'          => 'test-123',
            'accountId'   => 1,
            'amount'      => '100.00',
            'currency'    => 'EUR',
            'date'        => '2025-01-01',
            'description' => 'Platba kartou',
            'merchant'    => 'Supermarket',
        ]);

        $this->assertSame('Platba kartou', $transaction->getDescription());
    }

    /**
     * Test that getDescription returns (empty description) when all fields are empty.
     */
    public function testGetDescriptionReturnsEmptyDescription(): void
    {
        $transaction = Transaction::fromArray([
            'id'          => 'test-123',
            'accountId'   => 1,
            'amount'      => '100.00',
            'currency'    => 'EUR',
            'date'        => '2025-01-01',
            'merchant'    => 'Supermarket',
        ]);

        $this->assertSame('(empty description)', $transaction->getDescription());
    }

    /**
     * Test that getNotes appends original description when payee is used.
     * This preserves the original description text in the notes field.
     */
    public function testGetNotesAppendsDescriptionWhenPayeeUsed(): void
    {
        $transaction = Transaction::fromArray([
            'id'          => 'test-123',
            'accountId'   => 1,
            'amount'      => '100.00',
            'currency'    => 'EUR',
            'date'        => '2025-01-01',
            'description' => 'Platba kartou',
            'merchant'    => 'Supermarket',
            'payee'       => 'T-Mobile',
            'notes'       => 'ID: 123',
        ]);

        $this->assertSame('ID: 123 | Platba kartou', $transaction->getNotes());
    }

    /**
     * Test that getNotes returns only original notes when no description to append.
     */
    public function testGetNotesReturnsOnlyNotesWhenNoDescription(): void
    {
        $transaction = Transaction::fromArray([
            'id'          => 'test-123',
            'accountId'   => 1,
            'amount'      => '100.00',
            'currency'    => 'EUR',
            'date'        => '2025-01-01',
            'description' => '',
            'merchant'    => 'Supermarket',
            'payee'       => 'T-Mobile',
            'notes'       => 'ID: 123',
        ]);

        $this->assertSame('ID: 123', $transaction->getNotes());
    }

    /**
     * Test that getNotes returns only description when payee used but no original notes.
     */
    public function testGetNotesReturnsOnlyDescriptionWhenNoNotes(): void
    {
        $transaction = Transaction::fromArray([
            'id'          => 'test-123',
            'accountId'   => 1,
            'amount'      => '100.00',
            'currency'    => 'EUR',
            'date'        => '2025-01-01',
            'description' => 'Platba kartou',
            'merchant'    => 'Supermarket',
            'payee'       => 'T-Mobile',
        ]);

        $this->assertSame('Platba kartou', $transaction->getNotes());
    }

    /**
     * Test that getNotes returns empty string when description is used as main description.
     */
    public function testGetNotesReturnsEmptyWhenDescriptionUsedAsMain(): void
    {
        $transaction = Transaction::fromArray([
            'id'          => 'test-123',
            'accountId'   => 1,
            'amount'      => '100.00',
            'currency'    => 'EUR',
            'date'        => '2025-01-01',
            'description' => 'Payment for groceries',
            'merchant'    => 'Supermarket',
        ]);

        $this->assertSame('', $transaction->getNotes());
    }

    /**
     * Test that toLocalArray includes all new fields.
     */
    public function testToLocalArrayIncludesNewFields(): void
    {
        $transaction = Transaction::fromArray([
            'id'               => 'test-123',
            'accountId'        => 1,
            'amount'           => '100.00',
            'currency'         => 'EUR',
            'date'             => '2025-01-01',
            'description'      => 'Payment',
            'merchant'         => 'Supermarket',
            'payee'            => 'T-Mobile',
            'counterparty_name' => 'John Doe',
            'notes'            => 'Some notes',
        ]);

        $array = $transaction->toLocalArray();

        $this->assertArrayHasKey('payee', $array);
        $this->assertArrayHasKey('counterparty_name', $array);
        $this->assertArrayHasKey('notes', $array);
        $this->assertSame('T-Mobile', $array['payee']);
        $this->assertSame('John Doe', $array['counterparty_name']);
        $this->assertSame('Some notes', $array['notes']);
    }

    /**
     * Test that fromLocalArray properly handles new fields.
     */
    public function testFromLocalArrayHandlesNewFields(): void
    {
        $originalTransaction = Transaction::fromArray([
            'id'               => 'test-123',
            'accountId'        => 1,
            'amount'           => '100.00',
            'currency'         => 'EUR',
            'date'             => '2025-01-01',
            'description'      => 'Platba kartou',
            'merchant'         => 'Supermarket',
            'payee'            => 'T-Mobile',
            'counterparty_name' => 'John Doe',
            'notes'            => 'Some notes',
        ]);

        $localArray = $originalTransaction->toLocalArray();
        $restoredTransaction = Transaction::fromLocalArray($localArray);

        $this->assertSame('T-Mobile', $restoredTransaction->payee);
        $this->assertSame('John Doe', $restoredTransaction->counterpartyName);
        $this->assertSame('Some notes', $restoredTransaction->notes);
        $this->assertSame('T-Mobile', $restoredTransaction->getDescription());
    }
}
