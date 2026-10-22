<?php

namespace App\Services;

class EmailDomainService
{
    public function acceptsMail(string $domain): bool
    {
        // A valid domain is not proof that a particular mailbox exists.
        $records = @dns_get_record($domain, DNS_MX);
        if ($records === false) return false;

        foreach ($records as $record) {
            // A null MX (".") explicitly states that this domain accepts no mail.
            if (isset($record['target']) && rtrim($record['target'], '.') !== '') return true;
        }

        return false;
    }
}
