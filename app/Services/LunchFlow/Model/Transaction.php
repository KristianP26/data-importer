<?php

/*
 * Transaction.php
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

namespace App\Services\LunchFlow\Model;

use Carbon\Carbon;

/**
 * Class Transaction
 */
class Transaction
{
    public string $id;
    public int    $account;
    public string $amount;
    public string $currency;
    public Carbon $date;
    public string $description;
    public string $merchant;
    public string $payee;
    public string $counterpartyName;
    public string $notes;

    /**
     * Creates a transaction from a downloaded array.
     *
     * @param mixed $array
     */
    public static function fromArray($array): self
    {
        $object                   = new self();
        // mandatory fields:
        $object->id               = $array['id'];
        $object->account          = $array['accountId'];
        $object->amount           = (string)$array['amount'];
        $object->currency         = $array['currency'];
        $object->date             = Carbon::parse($array['date'], config('app.timezone'));
        $object->description      = trim($array['description'] ?? '');
        $object->merchant         = trim($array['merchant'] ?? '');
        // optional fallback fields for description:
        $object->payee            = trim($array['payee'] ?? '');
        $object->counterpartyName = trim($array['counterparty_name'] ?? '');
        $object->notes            = trim($array['notes'] ?? '');

        return $object;
    }

    /**
     * @return static
     */
    public static function fromLocalArray(array $array): self
    {
        $object                   = new self();

        // mandatory fields:
        $object->id               = $array['id'];
        $object->account          = $array['account'];
        $object->amount           = $array['amount'];
        $object->currency         = $array['currency'];
        $object->date             = $array['date'];
        $object->description      = $array['description'];
        $object->merchant         = $array['merchant'];
        // optional fallback fields for description:
        $object->payee            = $array['payee'] ?? '';
        $object->counterpartyName = $array['counterparty_name'] ?? '';
        $object->notes            = $array['notes'] ?? '';

        return $object;
    }

    public function getDate(): Carbon
    {
        return $this->date;
    }

    /**
     * Return transaction description, which depends on the values in the object.
     * Implements priority logic: merchant -> payee -> counterparty_name -> description -> (empty description)
     * Merchant/payee is prioritized because the description field often contains generic text
     * (e.g., "Platba kartou", "Card payment") while merchant contains the actual merchant name.
     */
    public function getDescription(): string
    {
        if ('' !== $this->merchant) {
            return $this->merchant;
        }
        if ('' !== $this->payee) {
            return $this->payee;
        }
        if ('' !== $this->counterpartyName) {
            return $this->counterpartyName;
        }
        if ('' !== $this->description) {
            return $this->description;
        }

        return '(empty description)';
    }

    public function getTransactionId(): string
    {
        return $this->id;
    }

    /**
     * Return name of the destination account
     */
    public function getDestinationName(): ?string
    {
        if ('' === $this->merchant) {
            return '(empty destination)';
        }

        return $this->merchant;
    }

    /**
     * Return transaction notes.
     * When merchant, payee or counterparty_name is used as description, the original description
     * is appended to notes to preserve the information.
     */
    public function getNotes(): string
    {
        $noteParts = [];

        // Add existing notes first
        if ('' !== $this->notes) {
            $noteParts[] = $this->notes;
        }

        // If merchant, payee or counterparty_name is used as description, append original description to notes
        if (('' !== $this->merchant || '' !== $this->payee || '' !== $this->counterpartyName) && '' !== $this->description) {
            $noteParts[] = $this->description;
        }

        return implode(' | ', $noteParts);
    }

    /**
     * Call this "toLocalArray" because we want to confusion with "fromArray", which is really based
     * on Lunch Flow information. Likewise, there is also "fromLocalArray".
     */
    public function toLocalArray(): array
    {
        return [
            'id'                => $this->id,
            'account'           => $this->account,
            'amount'            => $this->amount,
            'currency'          => $this->currency,
            'date'              => $this->date,
            'description'       => $this->description,
            'merchant'          => $this->merchant,
            'payee'             => $this->payee,
            'counterparty_name' => $this->counterpartyName,
            'notes'             => $this->notes,
        ];
    }
}
