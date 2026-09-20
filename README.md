# KomikByte

KomikByte is an academic PHP/MySQL manga and webtoon reader. Its wallet uses **simulated Philippine Peso values only**; there is no payment gateway, bank, card, GCash, PayPal, or real-world money flow.

## Quick start

1. Place this folder in XAMPP's `htdocs` directory, or point Apache at it.
2. Start Apache and MySQL in XAMPP.
3. Import `database/komikbyte.sql` in phpMyAdmin. If a prior, incomplete `komikbyte` database already exists, import `database/migrate_existing_komikbyte.sql` instead; it preserves existing users and completes the schema.
4. Confirm the local MySQL settings in `config/database.php` (XAMPP normally uses `root` with an empty password).
5. Open `http://localhost/<folder-name>/`.

## Important transaction guarantees

- Money is stored as MySQL `DECIMAL(10,2) UNSIGNED`, never `FLOAT` or `DOUBLE`.
- The unlock endpoint starts a transaction, locks the user wallet with `SELECT ... FOR UPDATE`, loads the chapter price from MySQL, conditionally deducts the balance, grants access, records the audit entry, then commits.
- The unique `(user_id, chapter_id)` key prevents duplicate ownership.
- Any exception rolls the whole transaction back. Failed purchases leave neither a wallet deduction nor an unlock record.
- The browser never sends a trusted balance, price, user ID, or purchase status.

## API map

| Endpoint | Method | Purpose |
| --- | --- | --- |
| `api/register.php` | POST | Register and start a session |
| `api/login.php`, `api/logout.php`, `api/session.php` | POST, POST, GET | Session lifecycle |
| `api/comics.php`, `api/comic.php`, `api/chapter.php` | GET | Catalog and protected reading |
| `api/wallet.php`, `api/topup.php`, `api/transactions.php` | GET, POST, GET | Wallet and audit history |
| `api/unlock.php`, `api/access.php` | POST, GET | Atomic purchase and library |

## Suggested TestQua test cases

| Scenario | Expected result |
| --- | --- |
| Top up `0`, negative, text, or `10000.01` | `422`; no wallet or audit update |
| Wallet `9.99`, chapter price `10.00` | `422`; still locked; balance unchanged |
| Wallet exactly equals chapter price | One successful purchase and a `0.00` balance |
| Purchase button double-click / two tabs | One `200`; later request gets `409`; one audit row |
| Send a client-provided price or balance | Ignored; database price and locked wallet are used |
| Force `chapter_access` insert failure inside `unlock.php` | Transaction rolls back, so no deduction persists |
| Read a paid chapter while logged out | `200` metadata with `can_read: false`, no content |
| Query another person's wallet or transactions | Impossible: APIs use only the session user ID |

For a rollback demonstration, temporarily make the `chapter_access` insert fail (for example, use an invalid chapter ID after the wallet lock in a development copy) and then confirm the user's balance and transaction history are unchanged.
