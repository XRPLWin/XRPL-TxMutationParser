<?php declare(strict_types=1);

namespace XRPLWin\XRPLOrderbookReader\Tests;

use PHPUnit\Framework\TestCase;
use XRPLWin\XRPLTxMutatationParser\TxMutationParser;

/***
 * @see https://github.com/XRPL-Labs/TxMutationParser/blob/main/test/tx7.ts
 * @see https://hash.xrp.fans/2CE935CC1FB07310E34DF373C95CE735FCB546577BA3C3E197F5F2CECAABB8B4/json
 */
final class Tx7Test extends TestCase
{
    public function testRegularKeySignedIsRegularKey()
    {
        $transaction = file_get_contents(__DIR__.'/fixtures/tx7.json');
        $transaction = \json_decode($transaction);
        $account = "raRWentc428obRZt8tDhDg2jXzkaizvqgY";
        $TxMutationParser = new TxMutationParser($account, $transaction->result);
        $parsedTransaction = $TxMutationParser->result();
        
        //Self (own account) must be $account
        $this->assertEquals($account,$parsedTransaction['self']['account']);
       
        # Basic info

        //Own account: zero balance change count
        $this->assertEquals(0,count($parsedTransaction['self']['balanceChanges']));

        //Transaction type REGULARKEYSIGNER
        $this->assertEquals(TxMutationParser::MUTATIONTYPE_REGULARKEYSIGNER,$parsedTransaction['type']);

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
            'account' => 'rQHYSEyxX3GKK3F6sXRvdd2NHhUqaxtC6F',
            'mutation' => [
                'counterparty' => 'rhub8VRN55s94qWKDv6jmDy1pUykJzF3wq',
                'currency' => "USD",
                'value' => "-0.1",
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
            'account' => 'rPdvC6ccq8hCdPKSPJkPmyZ4Mi1oG2FFkT',
            'mutation' => [
                'currency' => "XRP",
                'value' => "0.106294",
            ]
        ],$parsedTransaction['eventFlow']['end']);
    }
}