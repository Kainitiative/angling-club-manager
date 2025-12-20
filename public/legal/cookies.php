<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/layout/public_shell.php';

$pageTitle = 'Cookie Policy';

public_shell_start(['title' => $pageTitle]);
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      
      <h1 class="mb-4">Cookie Policy</h1>
      <p class="text-muted mb-4"><strong>Last Updated:</strong> December 2024</p>
      
      <p class="lead mb-5">This Cookie Policy explains how Club Manager Platform uses cookies and similar technologies when you visit our website.</p>

      <section class="mb-5">
        <h2>1. What Are Cookies?</h2>
        <p>Cookies are small text files that are placed on your device (computer, tablet, or mobile) when you visit a website. They are widely used to make websites work more efficiently and provide useful information to website owners.</p>
        <p>Cookies help us:</p>
        <ul>
          <li>Remember your login details so you don't have to sign in every time</li>
          <li>Understand how you use our platform</li>
          <li>Improve your experience</li>
          <li>Keep your account secure</li>
        </ul>
      </section>

      <section class="mb-5">
        <h2>2. Types of Cookies We Use</h2>
        
        <div class="card mb-3">
          <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="bi bi-check-circle me-2"></i>Essential Cookies (Required)</h5>
          </div>
          <div class="card-body">
            <p>These cookies are necessary for the Platform to function and cannot be switched off. They include:</p>
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>Cookie</th>
                  <th>Purpose</th>
                  <th>Duration</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><code>PHPSESSID</code></td>
                  <td>Maintains your login session</td>
                  <td>Session (deleted when you close browser)</td>
                </tr>
                <tr>
                  <td><code>csrf_token</code></td>
                  <td>Security - prevents cross-site request forgery</td>
                  <td>Session</td>
                </tr>
                <tr>
                  <td><code>cookie_consent</code></td>
                  <td>Remembers your cookie preferences</td>
                  <td>1 year</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card mb-3">
          <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Analytics Cookies (Optional)</h5>
          </div>
          <div class="card-body">
            <p>These cookies help us understand how visitors interact with our Platform. All data is anonymized.</p>
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>Cookie</th>
                  <th>Purpose</th>
                  <th>Duration</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><code>_ga</code></td>
                  <td>Google Analytics - distinguishes users</td>
                  <td>2 years</td>
                </tr>
                <tr>
                  <td><code>_ga_*</code></td>
                  <td>Google Analytics - maintains session state</td>
                  <td>2 years</td>
                </tr>
              </tbody>
            </table>
            <p class="mb-0 text-muted small">We use Google Analytics with IP anonymization enabled.</p>
          </div>
        </div>

        <div class="card mb-3">
          <div class="card-header bg-warning">
            <h5 class="mb-0"><i class="bi bi-gear me-2"></i>Functional Cookies (Optional)</h5>
          </div>
          <div class="card-body">
            <p>These cookies enable enhanced functionality and personalization:</p>
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>Cookie</th>
                  <th>Purpose</th>
                  <th>Duration</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><code>theme_preference</code></td>
                  <td>Remembers your display preferences</td>
                  <td>1 year</td>
                </tr>
                <tr>
                  <td><code>recent_club</code></td>
                  <td>Remembers your last viewed club</td>
                  <td>30 days</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card mb-3">
          <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="bi bi-box-arrow-up-right me-2"></i>Third-Party Cookies</h5>
          </div>
          <div class="card-body">
            <p>Some features may set cookies from third-party services:</p>
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>Service</th>
                  <th>Purpose</th>
                  <th>More Info</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><strong>Google</strong></td>
                  <td>Sign-in with Google, Analytics</td>
                  <td><a href="https://policies.google.com/privacy" target="_blank">Google Privacy Policy</a></td>
                </tr>
                <tr>
                  <td><strong>Stripe</strong></td>
                  <td>Payment processing</td>
                  <td><a href="https://stripe.com/privacy" target="_blank">Stripe Privacy Policy</a></td>
                </tr>
                <tr>
                  <td><strong>Social Media</strong></td>
                  <td>Share buttons, embedded content</td>
                  <td>See individual platform policies</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <section class="mb-5">
        <h2>3. Your Cookie Choices</h2>
        <p>When you first visit our Platform, you will see a cookie consent banner allowing you to:</p>
        <ul>
          <li><strong>Accept All:</strong> Enable all cookies</li>
          <li><strong>Essential Only:</strong> Only required cookies for the site to function</li>
          <li><strong>Customize:</strong> Choose which optional cookies to allow</li>
        </ul>
        <p>You can change your cookie preferences at any time by clicking the "Cookie Settings" link in our website footer.</p>
        
        <h5>Browser Controls</h5>
        <p>You can also control cookies through your browser settings. Most browsers allow you to:</p>
        <ul>
          <li>View what cookies are stored</li>
          <li>Delete cookies individually or all at once</li>
          <li>Block cookies from specific sites</li>
          <li>Block all cookies (note: this will affect site functionality)</li>
        </ul>
        <p>For instructions specific to your browser:</p>
        <ul>
          <li><a href="https://support.google.com/chrome/answer/95647" target="_blank">Google Chrome</a></li>
          <li><a href="https://support.mozilla.org/en-US/kb/clear-cookies-and-site-data-firefox" target="_blank">Mozilla Firefox</a></li>
          <li><a href="https://support.apple.com/en-ie/guide/safari/sfri11471/mac" target="_blank">Safari</a></li>
          <li><a href="https://support.microsoft.com/en-us/microsoft-edge/delete-cookies-in-microsoft-edge-63947406-40ac-c3b8-57b9-2a946a29ae09" target="_blank">Microsoft Edge</a></li>
        </ul>
      </section>

      <section class="mb-5">
        <h2>4. Similar Technologies</h2>
        <p>In addition to cookies, we may use similar technologies:</p>
        <ul>
          <li><strong>Local Storage:</strong> Stores data in your browser for faster performance</li>
          <li><strong>Session Storage:</strong> Temporary storage that's cleared when you close your browser</li>
        </ul>
        <p>These are used for the same purposes as cookies and are covered by this policy.</p>
      </section>

      <section class="mb-5">
        <h2>5. Updates to This Policy</h2>
        <p>We may update this Cookie Policy from time to time. Any changes will be posted on this page with an updated "Last Updated" date.</p>
      </section>

      <section class="mb-5">
        <h2>6. Contact Us</h2>
        <p>If you have questions about our use of cookies:</p>
        <address>
          <strong>Patrick Ryan Digital Design</strong><br>
          Email: [privacy@clubmanagerplatform.ie - To Be Updated]<br>
          Website: <a href="https://www.clubmanagerplatform.ie">www.clubmanagerplatform.ie</a>
        </address>
        <p>For more information about your data rights, please see our <a href="/public/legal/privacy.php">Privacy Policy</a>.</p>
      </section>

    </div>
  </div>
</div>

<?php
public_shell_end();
