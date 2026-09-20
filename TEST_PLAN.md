# KomikByte TestQua Test Plan

## Test environment

- PHP 8.x, MySQL/MariaDB (InnoDB), Apache/XAMPP
- Fresh registered test reader with a `0.00` wallet balance
- Paid test chapter: **Neon Ronin, Chapter 4**, price `₱10.00`
- All monetary operations use the API; do not modify `users.wallet_balance` from the browser.

## Functional and API tests

| ID | Scenario | Request / setup | Expected result |
| --- | --- | --- | --- |
| F-01 | Register | Valid username, email, 8+ character password | `201`; account/session created; `wallet_balance` is `0.00` |
| F-02 | Duplicate registration | Repeat existing email or username | `409`; no second account |
| F-03 | Login | Valid credentials | `200`; new session ID; wallet is returned |
| F-04 | Browse catalog | `GET /api/comics.php` | `200`; comic metadata only |
| F-05 | Read free chapter | `GET /api/chapter.php?id=<free>` | `200`, `can_read: true`, content returned |
| F-06 | Read locked paid chapter | Logged-out or unowned user requests a paid chapter | `200`, `can_read: false`, `content: null` |
| F-07 | Top up | Authenticated `POST /api/topup.php` with `50.00` | `200`; balance increases by exactly `50.00`; one `TOP_UP` audit row |
| F-08 | Successful unlock | Wallet `10.00`; `POST /api/unlock.php` with paid chapter ID | `200`; balance `0.00`; one access row; one purchase audit row |
| F-09 | Purchased content | Request the chapter after F-08 | `200`, `can_read: true`, content returned |
| F-10 | Wallet history | `GET /api/transactions.php` | Only this session user’s records, newest first |

## Boundary and negative tests

| ID | Input / condition | Expected result |
| --- | --- | --- |
| B-01 | Top up `0`, `0.00`, `-1`, empty, `abc` | `422`; no balance or audit change |
| B-02 | Top up `0.01` | `200`; exact `0.01` increase and audit row |
| B-03 | Top up `10000.00` | `200`; accepted maximum |
| B-04 | Top up `10000.01`, `1.234` | `422`; no change |
| B-05 | Wallet `9.99`, chapter price `10.00` | `422 Insufficient wallet balance`; no new rows |
| B-06 | Wallet `10.00`, chapter price `10.00` | `200`; final balance exactly `0.00` |
| B-07 | Wallet `10.01`, chapter price `10.00` | `200`; final balance exactly `0.01` |
| N-01 | Missing session on wallet, top-up, access, transaction, or unlock API | `401 Authentication required` |
| N-02 | Invalid/missing chapter ID | `422` for invalid ID, `404` for missing row; no balance change |
| N-03 | Send client `price`, `wallet_balance`, `user_id`, or `purchase_status` fields | Values are ignored; database/session values decide the result |
| N-04 | Send malformed JSON | `400 Invalid JSON request body` |
| N-05 | Purchase a free chapter through unlock API | `409`; user is not charged |
| N-06 | Repeat a completed unlock request | `409 Chapter already unlocked`; no second deduction or audit row |
| N-07 | SQL-injection-like login/search input | Prepared statements prevent query manipulation; normal validation/error result |

## Concurrency test

1. Create a test user and add exactly `₱10.00`.
2. From two browser tabs sharing the same signed-in session, submit the same paid chapter purchase at the same time.
3. Verify the results in the Network panel and database.

Expected:

- Exactly one request returns `200`.
- The other returns `409 Chapter already unlocked`.
- `users.wallet_balance` is `0.00`, never negative.
- There is exactly one `(user_id, chapter_id)` access row and one `CHAPTER_PURCHASE` success row.

This is protected by the wallet-row `SELECT ... FOR UPDATE`, a conditional non-negative deduction, and the unique `chapter_access(user_id, chapter_id)` key.

## Rollback demonstration (test database only)

Use a disposable copy of the database and a user with at least `₱10.00`. Add this temporary trigger, attempt an unlock, then remove the trigger:

```sql
DELIMITER //
CREATE TRIGGER qa_force_access_failure
BEFORE INSERT ON chapter_access
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'QA-forced access failure';
END//
DELIMITER ;

-- Call POST /api/unlock.php with a paid chapter here.

DROP TRIGGER qa_force_access_failure;
```

Expected after the failed endpoint call:

- Wallet balance is unchanged.
- No `chapter_access` row exists.
- No successful `wallet_transactions` row exists.

This demonstrates that deduction, access insertion, and audit recording are one atomic InnoDB transaction.

## ACID evidence

| Property | Evidence in KomikByte |
| --- | --- |
| Atomicity | `beginTransaction`, `commit`, and rollback on every failure path in `api/unlock.php` and `api/topup.php` |
| Consistency | Unsigned `DECIMAL(10,2)`, foreign keys, unique email/username/access keys, and server-side validation |
| Isolation | User wallet and chapter records are locked with `FOR UPDATE`; deduction has a non-negative predicate |
| Durability | InnoDB commits the balance, ownership, and immutable audit record before a successful API response |
