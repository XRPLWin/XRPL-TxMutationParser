<?php declare(strict_types=1);

namespace XRPLWin\XRPLOrderbookReader\Tests;

use PHPUnit\Framework\TestCase;
use XRPLWin\XRPLTxMutatationParser\TxMutationParser;

/***
 * @see https://github.com/XRPL-Labs/TxMutationParser/blob/main/test/tx12.ts
 * @see https://hash.xrp.fans/40181066402827E7AD3393C450E1AC1C5A1B055D0862182B7F8FA62759B76E68/json
 */
final class Tx12Test extends TestCase
{
    //NFT owner sends NFT to someone else (issuer perspective, rippling through own account)
    //XLS-10
    public function testNftOwnerSendsNftToSomeoneElse()
    {
        $transaction = file_get_contents(__DIR__.'/fixtures/tx12.json');
        $transaction = \json_decode($transaction);
        $account = "richard43NZXStHcjJi2UB8LGDQGFLKNs";
        $TxMutationParser = new TxMutationParser($account, $transaction->result);
        $parsedTransaction = $TxMutationParser->result();
        
        //Self (own account) must be $account
        $this->assertEquals($account,$parsedTransaction['self']['account']);
       
        # Basic info

        //Own account: two balance changes
        $this->assertEquals(2,count($parsedTransaction['self']['balanceChanges']));

        //Transaction type TRADE
        $this->assertEquals(TxMutationParser::MUTATIONTYPE_TRADE,$parsedTransaction['type']);

        $this->assertFalse($parsedTransaction['self']['feePayer']);

        # Event list

        //does not contain `primary` entry
        $this->assertArrayNotHasKey('primary',$parsedTransaction['eventList']);
        
        //does not contain `secondary` entry
        $this->assertArrayNotHasKey('secondary',$parsedTransaction['eventList']);

        # Event flow

        //contains (correct) `start` entry
        $this->assertArrayHasKey('start',$parsedTransaction['eventFlow']);
        $this->assertEquals([
            'account' => 'rcoinShpZ8MfUipcfbySaV7rPV82k1SMS',
            'mutation' => [
                'counterparty' => $account,
                'currency' => "021D001703B37004416E205852504C204E46543F",
                'value' => "-0.000000000000000000000000000000000000000000000000000000000000000000000000000000001",
            ]
        ],$parsedTransaction['eventFlow']['start']);

        //contains (correct) `intermediate` entry
        $this->assertArrayHasKey('intermediate',$parsedTransaction['eventFlow']);
        $this->assertEquals([
            'account' => $account,
            'mutations' => [
                'in' => null,
                'out' => null,
            ]
        ],$parsedTransaction['eventFlow']['intermediate']);

        //contains (correct) `end` entry
        $this->assertArrayHasKey('end',$parsedTransaction['eventFlow']);
        $this->assertEquals([
            'account' => 'rDMxhp4g689YyM7qfaarJRC6YMm74E3sMW',
            'mutation' => [
                'counterparty' => $account,
                'currency' => "021D001703B37004416E205852504C204E46543F",
                'value' => "0.000000000000000000000000000000000000000000000000000000000000000000000000000000001"
            ]
        ],$parsedTransaction['eventFlow']['end']);

    }
}