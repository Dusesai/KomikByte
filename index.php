<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="KomikByte — a simulated-wallet comic reader for academic transaction testing.">
    <title>KomikByte</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
    <div class="noise" aria-hidden="true"></div>
    <header class="site-header">
        <a class="brand" href="#/" aria-label="KomikByte home">
            <span class="brand-mark" style="background: var(--accent-red); color: white;">B</span>
            <span style="color: white; font-weight: bold;">Komik<span style="color: var(--accent-red);">Byte</span></span>
        </a>
        <nav class="main-nav" aria-label="Primary navigation">
            <a href="#/" data-nav="home" class="active">Home</a>
            <a href="#/" data-nav="novels">Hot</a>
            <a href="#/" data-nav="manhwa">Popular</a>
            <a href="#/" data-nav="manga">Latest</a>
            <a href="#/" data-nav="manhua">Completed</a>
            <a href="#/library" data-nav="library" class="auth-only">My Library</a>
            <a href="#/wallet" data-nav="wallet" class="auth-only">Wallet</a>
        </nav>
        <div class="header-actions">
            <!-- Search Bar moved to header like the Bangan UI -->
            <label class="search-box" style="margin-right: 15px;">
                <input id="header-search" type="search" placeholder="Search series..." autocomplete="off" style="background: #171923; border: none; color: white;">
            </label>
            
            <a id="wallet-chip" class="wallet-chip auth-only" href="#/wallet"><span>●</span> ₱<strong id="header-balance">0.00</strong></a>
            <button id="login-button" class="button button-quiet" style="color: white;">Log in</button>
            <button id="register-button" class="button button-primary" style="background: var(--accent-red); color: white; box-shadow: none;">Register</button>
            <button id="user-menu" class="user-menu auth-only" title="Log out" style="background: #171923; border: none; color: white;"><span id="user-initial" style="background: var(--accent-red); color: white;">U</span><span id="header-username">Reader</span></button>
        </div>
    </header>

    <main id="app" tabindex="-1">
        <section class="loading-page"><div class="spinner"></div><p>Loading KomikByte…</p></section>
    </main>

    <div id="toast-region" class="toast-region" aria-live="polite"></div>

    <dialog id="auth-dialog" class="auth-dialog">

        <!-- Close -->
        <button id="close-auth" class="dialog-close" type="button" aria-label="Close">
            ×
        </button>

        <!-- LEFT VISUAL PANEL -->
        <div class="auth-art">

            <div class="auth-art-top">
                <a href="#/" class="auth-brand">
                    <span class="auth-brand-mark">B</span>
                    <span>Komik<span>Byte</span></span>
                </a>
            </div>

            <div class="auth-art-middle">

                <span class="auth-art-eyebrow">
                    YOUR STORY AWAITS
                </span>

                <h2>
                    Read.<br>
                    Discover.<br>
                    <em>Keep going.</em>
                </h2>

                <p>
                    Discover manga, manhwa, manhua, novels and comics,
                    then build your own collection.
                </p>

            </div>

            <div class="auth-art-bottom">

                <div class="auth-feature">
                    <span>✦</span>
                    <div>
                        <strong>Build your collection</strong>
                        <small>Keep your unlocked chapters in one place.</small>
                    </div>
                </div>

                <div class="auth-feature">
                    <span>₱</span>
                    <div>
                        <strong>Simulated wallet</strong>
                        <small>Designed for academic transaction testing.</small>
                    </div>
                </div>

            </div>

            <!-- Decorative elements -->
            <div class="auth-orb auth-orb-one"></div>
            <div class="auth-orb auth-orb-two"></div>

        </div>


        <!-- RIGHT FORM PANEL -->
        <form id="auth-form" class="auth-form" novalidate>

            <!-- LOGIN / REGISTER SWITCH -->
            <div class="auth-toggle">

                <button
                    type="button"
                    data-mode="login"
                    class="active">
                    Log in
                </button>

                <button
                    type="button"
                    data-mode="register">
                    Create account
                </button>

            </div>


            <!-- FORM HEADER -->
            <div class="form-copy">

                <span
                    class="eyebrow"
                    id="auth-eyebrow">
                    WELCOME BACK
                </span>

                <h2 id="auth-title">
                    Pick up where you left off.
                </h2>

                <p id="auth-description">
                    Log in to reach your wallet and unlocked chapters.
                </p>

            </div>


            <!-- USERNAME — REGISTER ONLY -->
            <label
                id="username-field"
                class="form-field hidden">

                <span>Username</span>

                <input
                    name="username"
                    autocomplete="username"
                    maxlength="30"
                    placeholder="e.g. manga_lover">

            </label>


            <!-- LOGIN IDENTITY -->
            <label class="form-field">

                <span id="identity-label">
                    Email or username
                </span>

                <input
                    name="identity"
                    autocomplete="username"
                    required
                    placeholder="you@example.com">

            </label>


            <!-- EMAIL — REGISTER ONLY -->
            <label
                id="email-field"
                class="form-field hidden">

                <span>Email address</span>

                <input
                    name="email"
                    autocomplete="email"
                    maxlength="254"
                    placeholder="you@example.com">

            </label>


            <!-- PASSWORD -->
            <label class="form-field">

                <span>Password</span>

                <input
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    required
                    placeholder="At least 8 characters">

            </label>


            <!-- ERROR -->
            <p
                id="auth-error"
                class="form-error"
                role="alert">
            </p>


            <!-- SUBMIT -->
            <button
                id="auth-submit"
                class="button button-primary button-full"
                type="submit">

                Log in

            </button>


            <!-- SIMULATION NOTICE -->
            <div class="simulation-note">

                <span class="simulation-icon">₱</span>

                <div>
                    <strong>Academic simulation</strong>
                    <small>
                        Your wallet uses simulated funds.
                        No real money is involved.
                    </small>
                </div>

            </div>

        </form>

    </dialog>

    <dialog id="topup-dialog" class="small-dialog">
        <button class="dialog-close" data-close-dialog="topup-dialog" aria-label="Close">×</button>
        <span class="eyebrow">SIMULATED TOP-UP</span><h2>Add practice funds</h2>
        <p>These funds exist only in the KomikByte test database. Nothing is charged or transferred.</p>
        <form id="topup-form">
            <div class="amount-options">
                <button type="button" data-amount="50.00">₱50</button><button type="button" data-amount="100.00" class="selected">₱100</button><button type="button" data-amount="200.00">₱200</button><button type="button" data-amount="500.00">₱500</button>
            </div>
            <label class="form-field">Custom amount<input id="topup-amount" name="amount" inputmode="decimal" value="100.00" required></label>
            <p id="topup-error" class="form-error" role="alert"></p>
            <button class="button button-primary button-full" type="submit">Add simulated funds</button>
        </form>
    </dialog>

    <dialog id="purchase-dialog" class="small-dialog purchase-dialog">
        <button class="dialog-close" data-close-dialog="purchase-dialog" aria-label="Close">×</button>
        <span class="eyebrow">UNLOCK CHAPTER</span><h2 id="purchase-title">Ready to unlock?</h2>
        <div class="purchase-summary"><div><span>Chapter price</span><strong id="purchase-price">₱0.00</strong></div><div><span>Your balance</span><strong id="purchase-balance">₱0.00</strong></div></div>
        <p id="purchase-copy">The amount is retrieved from the database when you confirm. This is a simulated transaction.</p>
        <p id="purchase-error" class="form-error" role="alert"></p>
        <button id="confirm-purchase" class="button button-primary button-full">Confirm simulated purchase</button>
    </dialog>

    <template id="empty-template"><section class="empty-state"><span class="empty-icon">◌</span><h2>Nothing here yet</h2><p>There is more story waiting for you on the Discover page.</p><a href="#/" class="button button-primary">Discover comics</a></section></template>
    <script src="assets/js/app.js"></script>
</body>
</html>
