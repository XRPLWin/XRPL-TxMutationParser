<?php declare(strict_types=1);

namespace XRPLWin\XRPLTxMutatationParser\Tests;

use PHPUnit\Framework\TestCase;
use XRPLWin\XRPLTxMutatationParser\TxMutationParser;

/***
 * @see https://github.com/XRPL-Labs/TxMutationParser/blob/main/test/tx11.ts
 * @see https://hash.xrp.fans/2854762BC8FF1B96FB7231131C49054BF65EE5576C62400E80548E61B0CD1F50/json
 */
final class Tx11Test extends TestCase
{
    public function testRegular3XrpReceiving()
    {
        $transaction = file_get_contents(__DIR__.'/fixtures/tx11.json');
        $transaction = \json_decode($transaction);
        $account = "rLUmNB4HDBXceBoDTZwcMn2akcpSj44BaB";
        $TxMutationParser = new TxMutationParser($account, $transaction->result);
        $parsedTransaction = $TxMutationParser->result();
        
        //Self (own account) must be $account
        $this->assertEquals($account,$parsedTransaction['self']['account']);
       
        # Basic info

        //Own account: one balance change
        $this->assertEquals(1,count($parsedTransaction['self']['balanceChanges']));

        //Transaction type RECEIVED
        $this->assertEquals(TxMutationParser::MUTATIONTYPE_RECEIVED,$parsedTransaction['type']);

        $this->assertFalse($parsedTransaction['self']['feePayer']);

        # Event list

        //contains (correct) `primary` entry
        $this->assertArrayHasKey('primary',$parsedTransaction['eventList']);
        $this->assertEquals([
            'currency' => "XRP",
            'value' => "3",
        ],$parsedTransaction['eventList']['primary']);
        
        //does not contain `secondary` entry
        $this->assertArrayNotHasKey('secondary',$parsedTransaction['eventList']);

        # Event flow

        //contains (correct) `start` entry
        $this->assertArrayHasKey('start',$parsedTransaction['eventFlow']);
        $this->assertEquals([
            'account' => 'rwietsevLFg8XSmG3bEZzFein1g8RBqWDZ',
            'mutation' => [
                'currency' => "XRP",
                'value' => "-3",
            ]
        ],$parsedTransaction['eventFlow']['start']);

        //does not contain `intermediate` entry
        $this->assertArrayNotHasKey('intermediate',$parsedTransaction['eventFlow']);
        
        //contains (correct) `end` entry
        $this->assertArrayHasKey('end',$parsedTransaction['eventFlow']);
        $this->assertEquals([
            'account' => $account,
            'mutation' => [
                'currency' => "XRP",
                'value' => "3"
            ]
        ],$parsedTransaction['eventFlow']['end']);

    }
}