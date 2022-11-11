<?php declare(strict_types=1);

namespace XRPLWin\XRPLOrderbookReader\Tests;

use PHPUnit\Framework\TestCase;
use XRPLWin\XRPLTxMutatationParser\TxMutationParser;

/***
 * @see https://github.com/XRPL-Labs/TxMutationParser/blob/main/test/tx13.ts
 * @see https://hash.xrp.fans/2854762BC8FF1B96FB7231131C49054BF65EE5576C62400E80548E61B0CD1F50/json
 */
final class Tx13Test extends TestCase
{
    public function testTx13_PartialPaymentSender()
    {
        $transaction = file_get_contents(__DIR__.'/fixtures/tx13.json');
        $transaction = \json_decode($transaction);
        $account = "richard43NZXStHcjJi2UB8LGDQGFLKNs";
        $TxMutationParser = new TxMutationParser($account, $transaction->result);
        $parsedTransaction = $TxMutationParser->result();
        
        //Self (own account) must be $account
        $this->assertEquals($account,$parsedTransaction['self']['account']);
       
        # Basic info

        //Own account: zero balance change count
        $this->assertEquals(0,count($parsedTransaction['self']['balanceChanges']));

        //Transaction type UNKNOWN
        $this->assertEquals(TxMutationParser::MUTATIONTYPE_UNKNOWN,$parsedTransaction['type']);

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
            'account' => 'rwietsevLFg8XSmG3bEZzFein1g8RBqWDZ',
            'mutation' => [
                'currency' => 'XRP',
                'value' => '-3.000012'
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
            'account' => 'rLUmNB4HDBXceBoDTZwcMn2akcpSj44BaB',
            'mutation' => [
                'currency' => 'XRP',
                'value' => '3'
            ]
        ],$parsedTransaction['eventFlow']['end']);

    }
}