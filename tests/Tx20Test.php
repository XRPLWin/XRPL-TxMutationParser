<?php declare(strict_types=1);

namespace XRPLWin\XRPLTxMutatationParser\Tests;

use PHPUnit\Framework\TestCase;
use XRPLWin\XRPLTxMutatationParser\TxMutationParser;

/***
 * @see https://hash.xrp.fans/C842D3D41CD48D707F18374641721F4DA5596E5C13D63AE23DF450ACA982DA9C/json
 * Return issued currency to issuer (delivered_amount only)
 */
final class Tx20Test extends TestCase
{
    public function testPaymentSenderSentsBackFundsToIssuer()
    {
        $transaction = file_get_contents(__DIR__.'/fixtures/tx20.json');
        $transaction = \json_decode($transaction);
        $account = "rGWv5YTG4ATZS6okStexXV5ZPRbGqb7E3k";
        $TxMutationParser = new TxMutationParser($account, $transaction->result);
        $parsedTransaction = $TxMutationParser->result();
        //dd($parsedTransaction);
        //Self (own account) must be $account
        $this->assertEquals($account,$parsedTransaction['self']['account']);

        # Basic info

        //Own account: one balance change
        $this->assertEquals(1,count($parsedTransaction['self']['balanceChanges']));

        //Transaction type SENT
        $this->assertEquals(TxMutationParser::MUTATIONTYPE_SENT,$parsedTransaction['type']);

        $this->assertTrue($parsedTransaction['self']['feePayer']);

        # Event list

        //does contain `primary` entry
        $this->assertArrayHasKey('primary',$parsedTransaction['eventList']);
        $this->assertEquals([
            'currency' => '53796D626F6C6F67790000000000000000000000',
            'counterparty' => 'rMK4cYevA3vBMhiSDd76WrT8Rrhe6wqUB2',
            'value' => '333'
        ],$parsedTransaction['eventList']['primary']);

        
    }

    public function testPaymentIssuerRecievesReturnedFunds()
    {
        $transaction = file_get_contents(__DIR__.'/fixtures/tx20.json');
        $transaction = \json_decode($transaction);
        $account = "rMK4cYevA3vBMhiSDd76WrT8Rrhe6wqUB2";
        $TxMutationParser = new TxMutationParser($account, $transaction->result);
        $parsedTransaction = $TxMutationParser->result();
        
        //Self (own account) must be $account
        $this->assertEquals($account,$parsedTransaction['self']['account']);

        # Basic info

        //Own account: one balance change
        $this->assertEquals(0,count($parsedTransaction['self']['balanceChanges']));

        //Transaction type SENT
        $this->assertEquals(TxMutationParser::MUTATIONTYPE_RECEIVED,$parsedTransaction['type']);

        $this->assertFalse($parsedTransaction['self']['feePayer']);

        # Event list

        //does NOT contain `primary` entry
        $this->assertArrayNotHasKey('primary',$parsedTransaction['eventList']);
    }
}