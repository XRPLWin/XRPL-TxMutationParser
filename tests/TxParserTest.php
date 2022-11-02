<?php declare(strict_types=1);

namespace XRPLWin\XRPLOrderbookReader\Tests;

use PHPUnit\Framework\TestCase;
use XRPLWin\XRPLTxMutatationParser\TxMutationParser;

final class TxParserTest extends TestCase
{
    public function testTx1_RipplinTroughOwnAccount()
    { 
        //https://hash.xrp.fans/D36265AD359D82BDF056CAFE760F9DFF42BB21C308EC3F68C4DE0D707D2FB6B6/json

        $transaction = file_get_contents(__DIR__.'/fixtures/tx1.json');
        $transaction = \json_decode($transaction);
        $account = "r38UeRHhNLnprf1CjJ3ts4y1TuGCSSY3hL";
        $parsedTransaction = new TxMutationParser($account, $transaction->result);
    }
}