**Sample template.** Replace every text in square brackets and every example value with what you actually recorded. Mask account numbers, names, bank details, IDs and references before publishing. The post title is the page heading (H1), so this file starts with the first section.

## Test overview

| Item | Value |
| :--- | :--- |
| Broker | [Broker name] |
| Country of the test account | [Country] |
| Legal entity that opened the account | [Entity name] |
| Account type | [Standard] |
| Test period | [1 to 14 October 2026] |
| Tested by | [Researcher name] |

This record shows what we did, when, and what happened. Results apply to the entity, account type and period above.

## Testing methodology

1. Open a live account as a resident of [country].
2. Deposit with the local methods offered to residents.
3. Measure spreads at fixed times over several sessions.
4. Place test orders and record execution.
5. Contact customer support with the same questions.
6. Withdraw the balance and record every step.

Times are in [UTC+7]. Screenshots are kept for every step; personal data is blurred.

## Account registration

- Registration started: [date, time]
- Identity documents requested: [list]
- Account approved: [date, time] (*[duration]*)

Note anything unusual, for example extra documents or a change of entity during sign-up.

## Deposit and withdrawal tests

### Deposits

| Method | Amount | Fee | Requested | Credited | Result |
| :--- | ---: | ---: | :--- | :--- | :--- |
| [Local bank transfer] | [$100] | [$0] | [date, time] | [date, time] | [Completed] |
| [E-wallet] | [$50] | [$0] | [date, time] | [date, time] | [Completed] |

### Withdrawals

| Method | Amount | Fee | Requested | Received | Result |
| :--- | ---: | ---: | :--- | :--- | :--- |
| [Local bank transfer] | [$80] | [$0] | [date, time] | [date, time] | [Completed] |

Account and reference numbers: masked, for example `****4821`.

## Spread tests

### [Standard] account

| Session | Instrument | Lowest | Average | Highest | Samples |
| :--- | :--- | ---: | ---: | ---: | ---: |
| [London open] | EUR/USD | [0.9 pips] | [1.1 pips] | [1.6 pips] | [60] |
| [London open] | XAU/USD | [15 cents] | [20 cents] | [32 cents] | [60] |
| [Daily rollover] | EUR/USD | [1.0 pips] | [2.4 pips] | [6.0 pips] | [30] |

### [Raw spread] account

| Session | Instrument | Lowest | Average | Highest | Samples |
| :--- | :--- | ---: | ---: | ---: | ---: |
| [London open] | EUR/USD | [0.0 pips] | [0.1 pips] | [0.4 pips] | [60] |

Commission is charged separately: [amount per lot, round turn].

## Execution tests

| Date and time | Instrument | Order type | Volume | Execution time | Slippage | Result |
| :--- | :--- | :--- | ---: | ---: | ---: | :--- |
| [date, time] | EUR/USD | Market buy | [0.10] | [85 ms] | [0.0 pips] | [Filled] |
| [date, time] | XAU/USD | Market sell | [0.05] | [120 ms] | [-0.2 pips] | [Filled] |

## Customer support tests

| Channel | Language | Question | First response | Result |
| :--- | :--- | :--- | :--- | :--- |
| [Live chat] | [Vietnamese] | [How long do withdrawals take?] | [2 minutes] | [Answered] |
| [Email] | [English] | [Which entity holds my account?] | [6 hours] | [Answered] |

## Evidence notes

> Screenshots and records are kept for every step above. Personal data, account numbers and references are masked.

- [Note about anything that could change the result]
- [Note about data we could not verify]

To add a screenshot, upload it to the Media Library and put its address on its own line, for example:

![Masked deposit confirmation](https://example.com/wp-content/uploads/deposit-confirmation.png "Deposit confirmation, personal data masked")

## Conclusion

Summarise the results in plain words: what worked, what took longer than stated, and what we will test again.

---

## Template guide (delete this section before publishing)

How the Markdown in this file becomes blocks when you import it:

| Markdown | Block in the editor |
| :--- | :--- |
| `## Heading` to `###### Heading` | Heading H2 to H6 (`#` becomes H2) |
| Text separated by an empty line | Paragraph |
| `**bold**`, `*italic*`, `[text](https://...)` | Bold, italic, link inside the text |
| `- item` or `1. item` (2 spaces to nest) | List |
| `> text` | Quote |
| Rows with `\|` and a `---` row under the header | Table (style "Data table") |
| `---` on its own line | Separator |
| `![alt text](https://.../image.jpg "Caption")` on its own line | Image (only images that exist; others are listed in the preview and skipped) |

The structured test records (deposits, withdrawals, spread, execution and support tests with their screenshots) are not imported from Markdown: enter them in the Evidence boxes below the editor so they appear in the Evidence data blocks.
