<?php declare(strict_types=1);

namespace XRPLWin\XRPLTxMutatationParser\Tests;

use PHPUnit\Framework\TestCase;
use XRPLWin\XRPLTxMutatationParser\TxMutationParser;

/***
 * Trading Fees test
 */
final class Tx28Test extends TestCase
{
    public function testMPTVaultWithdrawWithdrawerAccount()
    {
        $transaction = file_get_contents(__DIR__.'/fixtures/tx28.json');
        $transaction = \json_decode($transaction);

        $account = "rKkNGQLEAbVPHu2C3cPAnQ6RQjRk5Yi2Sx";
        //dd($transaction->result);
        $TxMutationParser = new TxMutationParser($account, $transaction->result, true);

        $parsedTransaction = $TxMutationParser->result();

        $this->assertEquals([
          [
            "mpt_issuance_id" => "0000000190FBDFAA06CC65CB51EB7925B1EA210C79F07136",
            "value" => "-99"
          ],
          [
            "currency" => "XRP",
            "value" => "-0.0005"
          ],
          [
            "mpt_issuance_id" => "00381376C7A84BE22C567D0BE94CC2A9B7B936B6D48CF7C6",
            "value" => "99"
          ]
        ],$parsedTransaction['self']['balanceChanges']);

        $this->assertEquals([
          [
            "mpt_issuance_id" => "0000000190FBDFAA06CC65CB51EB7925B1EA210C79F07136",
            "value" => "-99"
          ],
          [
            "mpt_issuance_id" => "00381376C7A84BE22C567D0BE94CC2A9B7B936B6D48CF7C6",
            "value" => "99"
          ]
        ],\array_values($parsedTransaction['self']['balanceChangesExclFee']));
    }

    public function testMPTVaultWithdrawVaultAccount()
    {
        $transaction = file_get_contents(__DIR__.'/fixtures/tx28.json');
        $transaction = \json_decode($transaction);

        $account = "rNDcsfdJhSkwUckzrXmVQUHzcdeb5kwinU";
        
        $TxMutationParser = new TxMutationParser($account, $transaction->result, true);

        $parsedTransaction = $TxMutationParser->result();
        $this->assertEquals([
          [
            "mpt_issuance_id" => "00381376C7A84BE22C567D0BE94CC2A9B7B936B6D48CF7C6",
            "value" => "-99"
          ],
          [
            "mpt_issuance_id" => "0000000190FBDFAA06CC65CB51EB7925B1EA210C79F07136",
            "value" => "99"
          ]
        ],$parsedTransaction['self']['balanceChanges']);

        $this->assertEquals([
          [
            "mpt_issuance_id" => "00381376C7A84BE22C567D0BE94CC2A9B7B936B6D48CF7C6",
            "value" => "-99"
          ],
          [
            "mpt_issuance_id" => "0000000190FBDFAA06CC65CB51EB7925B1EA210C79F07136",
            "value" => "99"
          ]
        ],$parsedTransaction['self']['balanceChangesExclFee']);
    }

}