<?php
/**
 * R3D KAS Manager
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.24.2-alpha
 * @date      2025-10-10
 * @license   MIT License
 *
 * app\Jobs\CheckPropagationJob.php
 */

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckPropagationJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public string $domain;
    public array $expectations;
    public int $triesLeft;

    /**
     * @param string $domain (e.g. r3d.de)
     * @param array $expectations e.g. ['A'=>['178.63.15.195'],'MX'=>['w0205999.kasserver.com.']]
     * @param int $tries number of attempts (default: 144 -> 24h with 10min)
     */
    public function __construct(string $domain, array $expectations = [], int $tries = 144)
    {
        $this->domain = $domain;
        $this->expectations = $expectations;
        $this->triesLeft = $tries;
    }

    public function handle(): void
    {
        $domain = $this->domain;
        $okForAll = true;

        foreach ($this->expectations as $type => $vals) {
            $flag = $this->dnsTypeFlag($type);
            $found = @dns_get_record($domain, $flag);
            $values = [];

            if (!empty($found) && is_array($found)) {
                foreach ($found as $rec) {
                    $values[] = $this->normalizeDnsValue($rec, $type);
                }
            }

            // normalize expected values
            $expected = array_map(fn($v) => rtrim($v, '.'), $vals);
            $valuesClean = array_map(fn($v) => rtrim($v, '.'), $values);

            if (!count(array_intersect($expected, $valuesClean))) {
                $okForAll = false;
                break;
            }
        }

        if ($okForAll) {
            // propagation confirmed -> dispatch stage 2
            \Log::info("Propagation confirmed for {$domain}; dispatching PostPropagationRunner.");
            dispatch(new PostPropagationRunner($domain));
            return;
        }

        $this->triesLeft--;
        if ($this->triesLeft > 0) {
            // requeue after 10 minutes
            dispatch((new CheckPropagationJob($this->domain, $this->expectations, $this->triesLeft))->delay(now()->addMinutes(10)));
            \Log::info("Propagation check for {$domain} not ready - {$this->triesLeft} attempts left.");
        } else {
            \Log::warning("Propagation check for {$domain} timed out after attempts.");
            // optionally notify admins or write a recipe_run failure
        }
    }

    protected function dnsTypeFlag(string $type): int
    {
        return match(strtoupper($type)) {
            'A' => DNS_A,
            'AAAA' => DNS_AAAA,
            'MX' => DNS_MX,
            'TXT' => DNS_TXT,
            default => DNS_A,
        };
    }

    protected function normalizeDnsValue(array $record, string $type): string
    {
        $type = strtoupper($type);
        return match($type) {
            'MX' => ($record['target'] ?? $record['exchange'] ?? $record['target'] ?? '') ,
            'A' => $record['ip'] ?? '',
            'AAAA' => $record['ipv6'] ?? $record['ipv6addr'] ?? '',
            'TXT' => implode('', $record['entries'] ?? ($record['txt'] ?? [])),
            default => json_encode($record),
        };
    }
}
