[![CI workflow](https://github.com/XRPLWin/XRPL-TxMutationParser/actions/workflows/main.yml/badge.svg)](https://github.com/XRPLWin/XRPL-TxMutationParser/actions/workflows/main.yml)
[![GitHub license](https://img.shields.io/github/license/XRPLWin/XRPL-TxMutationParser)](https://github.com/XRPLWin/XRPL-TxMutationParser/blob/main/LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/xrplwin/xrpl-txmutationparser.svg?style=flat)](https://packagist.org/packages/xrplwin/xrpl-txmutationparser)

# XRPL Transaction Mutation Parser for PHP

## Demo

See this in action on [XRPLWin Playground](https://playground.xrpl.win/play/xrpl-transaction-mutation-parser)

## Description

Parse XRPL transaction to context aware object for visual representation. It takes a XRPL transaction (outcome, meta) and an XRPL account. The XRPL account is the context from which the XPRL transaction is to be interpreted.

The account can be the sender, recipient, or an intermediate account. An intermediate account applies if e.g. there's a trade happening, touching your own offer asynchronously. You put up an offer and at some point down the road it gets (possibly partially) consumed. Alternatively, you can be an Intermediate account if you are a regular key signer or if something is rippling through your account.

The lib. then parses everything, performs all logic (include fee or not, etc.) and returns an object that is ready for use in e.g. an event list, or transaction details view, with all relevant objects parsed & calculated.

This is PHP port of https://github.com/XRPL-Labs/TxMutationParser by [@XRPL Labs](https://github.com/XRPL-Labs)

### Note

This package is provided as is, please test it yourself first.  
Found a bug? [Report issue here](https://github.com/XRPLWin/XRPL-TxMutationParser/issues/new)

## Requirements
- PHP 8.1 or higher
- [Composer](https://getcomposer.org/)

## Installation
To install run

```
composer require xrplwin/xrpl-txmutationparser
```

## Usage
```PHP
use XRPLWin\XRPLTxMutatationParser\TxMutationParser;

$referenceAccount = "rA...";
$tx = (object)[ // Full XRPL transaction, containing Account, Destination, meta, ...)
    "Account": "rA...",
    "Amount": "100300000",
    "Destination": "rD....",
    "Fee": "10000",
    ...
    "meta" => [ ... ],
    ...
];

//Enable to include trading fees calculation in allBalanceChanges results
$calculateTradingFees = false;

$TxMutationParser = new TxMutationParser($referenceAccount, $tx, $calculateTradingFees);
$parsedTransaction = $TxMutationParserRef->result();

print_r($parsedTransaction); 
```

A sample response (as JSON):

```javascript
{
  self: {
    feePayer: true,
    account: 'rA...',
    balanceChangesExclFee: [ [Object], [Object], ... ],
    balanceChanges: [ [Object], [Object], ... ]
  },
  type: 'TRADE',
  eventList: {
    primary: { ... },
    secondary: { ... },
  },
  eventFlow: {
    start: {
      account: 'rB...',
      mutation: [Object]
    },
    intermediate: {
      account: 'rA...',
      mutations: {'in' => ?Object, 'out' => ?Object}
    },
    end: {
      account: 'rC...',
      mutation: [Object]
    }
  },
  allBalanceChanges: {
    rB: [ [Object], [Object], ... ],
    rA: [ [Object], [Object], ... ],
    rC: [ [Object], ... ]
  }
}
```

## Scenario's (data contents)

#### Event List (`eventList`, e.g. a list with transactions belonging to the context account)

If no balance changes to your context account applied: empty. If only one relevant change (e.g. payment in / out): only the `eventList.primary` object exists. If a trade happened and your account both sent and received / exchanged something, the `eventList.primary` object is the main balance change. For reference, the `eventList.secondary` value can be displayed as well.

`eventList.secondary.counterparty` can be array of counterparties if viewing account is issuer and multi balances of currency is adjusted (rippled) trough multiple parties ([see sample here](https://playground.xrpl.win/play/xrpl-transaction-mutation-parser?hash=A357FD7C8F0BBE7120E62FD603ACBE98819BC623D5D12BD81AC68564393A7792&ref=rhub8VRN55s94qWKDv6jmDy1pUykJzF3wq)) where `eventList.secondary.value` is SUM of balance changes of that currency - this behaviour is different from XRPL Labs package.

A common scenario where the `eventList` is completely empty, is if your context account is eg. the account an issued currency
rippled through, or the context account is the regular key, signing the transaction parsed.

#### Event Flow (`eventFlow`, e.g. transaction details page viewed by the context account)

The `eventFlow` object can contain a `eventFlow.start`, `eventFlow.intermediate` and `eventFlow.end` object. The `start` and `end` object can contain a `mutation` (one, so e.g. `eventFlow.start.mutation`).

The `eventFlow.intermediate` object can contain multiple `mutations`, an `in` and `out` (or only `in`, or (more commonly) only `out`) mutation: `eventFlow.intermediate.mutations.in` / `eventFlow.intermediate.mutations.out`.

The `eventFlow` object can contain **only** a `intermediate` object (so no `start` and `end` object) if:

- The transaction is of the mutation type `SET` (e.g. Regular Key set, AccountSet, putting up an XRPL offer, etc.)
- The context account is the issuer of a transaction, and the transaction is rippling through
- The context account is the signer using a regular key, and no balance changes apply to the context account

##### Display logic:

Always show then in this order (if present)

- start
- intermediate
- end

... And only (but always) show them if they are present.


#### Balance changes (`allBalanceChanges`)
All Balance Changes returns balance changes for every participant. If `calculateTradingFees` enabled, balance changes also include trading fee calculations. 

## Running tests
Run all tests in "tests" directory.
```
composer test
```
or
```
./vendor/bin/phpunit --testdox
```
