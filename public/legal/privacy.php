<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/layout/public_shell.php';

$pageTitle = 'Privacy Policy';

public_shell_start(['title' => $pageTitle]);
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      
      <h1 class="mb-4">Privacy Policy</h1>
      <p class="text-muted mb-4"><strong>Last Updated:</strong> January 2026</p>
      
      <div class="alert alert-success mb-5">
        <h5 class="alert-heading mb-2"><i class="bi bi-shield-check me-2"></i>Our Commitment to You</h5>
        <ul class="mb-0">
          <li><strong>Your data is safe</strong> - We use industry-standard security to protect your information</li>
          <li><strong>We never sell your data</strong> - Your personal information will never be sold to third parties</li>
          <li><strong>Your uploads are private</strong> - Photos and content are only visible to your club as intended</li>
        </ul>
      </div>

      <section class="mb-5">
        <h2>1. Who We Are</h2>
        <p>Angling Ireland ("we", "us", "our") is operated by:</p>
        <address>
          <strong>Patrick Ryan Digital Design</strong><br>
          Ireland<br>
          Email: <a href="mailto:privacy@anglingireland.ie">privacy@anglingireland.ie</a>
        </address>
        <p>Website: <a href="https://www.anglingireland.ie">www.anglingireland.ie</a></p>
      </section>

      <section class="mb-5">
        <h2>2. What Personal Data We Collect</h2>
        <p>We collect the following types of personal data:</p>
        
        <h5>Account Information</h5>
        <ul>
          <li>Name and email address</li>
          <li>Phone number (optional)</li>
          <li>Town, city, and country</li>
          <li>Profile picture</li>
          <li>Account password (encrypted)</li>
        </ul>

        <h5>Club Membership Data</h5>
        <ul>
          <li>Club memberships and roles</li>
          <li>Committee positions held</li>
          <li>Membership status and history</li>
        </ul>

        <h5>Content You Create</h5>
        <ul>
          <li>Catch logs (species, weight, length, notes)</li>
          <li>Catch photos with optional geolocation data</li>
          <li>Messages sent within the platform</li>
          <li>Competition entries and results</li>
        </ul>

        <h5>Technical Data</h5>
        <ul>
          <li>IP address and browser type</li>
          <li>Device information</li>
          <li>Pages visited and actions taken</li>
          <li>Cookies and similar technologies</li>
        </ul>
      </section>

      <section class="mb-5">
        <h2>3. How We Collect Your Data</h2>
        <ul>
          <li><strong>Directly from you:</strong> When you register, create a profile, log catches, or contact us</li>
          <li><strong>Automatically:</strong> Through cookies and analytics when you use our platform</li>
          <li><strong>From third parties:</strong> If you sign in using Google or social media accounts</li>
        </ul>
      </section>

      <section class="mb-5">
        <h2>4. Why We Process Your Data</h2>
        <table class="table table-bordered">
          <thead class="table-light">
            <tr>
              <th>Purpose</th>
              <th>Legal Basis</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Provide the Angling Ireland service</td>
              <td>Contract performance</td>
            </tr>
            <tr>
              <td>Manage your account and club memberships</td>
              <td>Contract performance</td>
            </tr>
            <tr>
              <td>Send service notifications (membership updates, etc.)</td>
              <td>Legitimate interest</td>
            </tr>
            <tr>
              <td>Improve our platform through analytics</td>
              <td>Legitimate interest</td>
            </tr>
            <tr>
              <td>Send marketing communications</td>
              <td>Consent (you can opt out anytime)</td>
            </tr>
            <tr>
              <td>Comply with legal obligations</td>
              <td>Legal obligation</td>
            </tr>
          </tbody>
        </table>
      </section>

      <section class="mb-5">
        <h2>5. Who We Share Your Data With</h2>
        <p>We share data only with trusted service providers who help us operate the platform:</p>
        <ul>
          <li><strong>Google:</strong> Analytics and optional sign-in services</li>
          <li><strong>Cloud hosting provider:</strong> Secure data storage</li>
          <li><strong>Email service provider:</strong> System notifications</li>
        </ul>
        <p><strong>We never sell your personal data to third parties for marketing or any other purpose.</strong></p>
        <p>Your club administrators can see your membership details and activity within their club. Your catch logs and photos are visible to other club members unless you choose otherwise.</p>
      </section>

      <section class="mb-5">
        <h2>6. International Data Transfers</h2>
        <p>Some of our service providers (such as Google) may process data outside the European Economic Area (EEA). When this happens, we ensure appropriate safeguards are in place, including:</p>
        <ul>
          <li>EU-approved Standard Contractual Clauses</li>
          <li>Adequacy decisions by the European Commission</li>
          <li>Data processing agreements with all third-party providers</li>
        </ul>
      </section>

      <section class="mb-5">
        <h2>7. How Long We Keep Your Data</h2>
        <ul>
          <li><strong>Active accounts:</strong> Data is retained while your account is active</li>
          <li><strong>Deleted accounts:</strong> Personal data is anonymized. Catch logs and club records may be retained in anonymized form for club statistics and records</li>
          <li><strong>Left a club:</strong> Your membership records are anonymized (shown as "Former Member") but club records are preserved</li>
        </ul>
      </section>

      <section class="mb-5">
        <h2>8. Your Rights Under GDPR</h2>
        <p>As a user in the EU/EEA, you have the following rights:</p>
        <ul>
          <li><strong>Right of Access:</strong> Request a copy of your personal data</li>
          <li><strong>Right to Rectification:</strong> Correct inaccurate or incomplete data</li>
          <li><strong>Right to Erasure:</strong> Request deletion of your data ("right to be forgotten")</li>
          <li><strong>Right to Restrict Processing:</strong> Limit how we use your data</li>
          <li><strong>Right to Data Portability:</strong> Receive your data in a machine-readable format</li>
          <li><strong>Right to Object:</strong> Object to processing based on legitimate interests</li>
          <li><strong>Right to Withdraw Consent:</strong> Withdraw consent at any time where processing is based on consent</li>
        </ul>
        <p>To exercise any of these rights, contact us at <a href="mailto:privacy@anglingireland.ie">privacy@anglingireland.ie</a>. We will respond within 30 days.</p>
      </section>

      <section class="mb-5">
        <h2>9. Data Security</h2>
        <p>We take the security of your data seriously and implement appropriate technical and organizational measures, including:</p>
        <ul>
          <li>Encryption of data in transit (HTTPS/TLS)</li>
          <li>Secure password storage using industry-standard hashing</li>
          <li>Regular security updates and monitoring</li>
          <li>Access controls limiting who can view your data</li>
          <li>Regular backups to prevent data loss</li>
        </ul>
      </section>

      <section class="mb-5">
        <h2>10. Children and Young People</h2>
        <p>The minimum age to create an account is <strong>16 years</strong> (Ireland's digital age of consent).</p>
        <p>Junior members (under 16) can only be added to clubs when:</p>
        <ul>
          <li>A parent or guardian is an active member of the same club</li>
          <li>The junior account is linked to the parent's account</li>
          <li>Parental consent has been obtained</li>
        </ul>
        <p>Parents/guardians can manage and delete their child's account at any time.</p>
      </section>

      <section class="mb-5">
        <h2>11. Cookies</h2>
        <p>We use cookies to operate our platform effectively. For full details, please see our <a href="/public/legal/cookies.php">Cookie Policy</a>.</p>
      </section>

      <section class="mb-5">
        <h2>12. Changes to This Policy</h2>
        <p>We may update this Privacy Policy from time to time. We will notify you of significant changes by:</p>
        <ul>
          <li>Posting a notice on our platform</li>
          <li>Sending an email to registered users</li>
          <li>Updating the "Last Updated" date at the top of this page</li>
        </ul>
      </section>

      <section class="mb-5">
        <h2>13. Contact Us</h2>
        <p>For any questions about this Privacy Policy or to exercise your data rights:</p>
        <address>
          <strong>Patrick Ryan Digital Design</strong><br>
          Email: <a href="mailto:privacy@anglingireland.ie">privacy@anglingireland.ie</a>
        </address>
      </section>

      <section class="mb-5">
        <h2>14. Complaints</h2>
        <p>If you are not satisfied with how we handle your data, you have the right to lodge a complaint with the Irish Data Protection Commission:</p>
        <address>
          <strong>Data Protection Commission</strong><br>
          21 Fitzwilliam Square South<br>
          Dublin 2, D02 RD28<br>
          Ireland<br>
          Website: <a href="https://www.dataprotection.ie" target="_blank">www.dataprotection.ie</a><br>
          Phone: +353 1 765 0100 / 1800 437 737<br>
          Email: info@dataprotection.ie
        </address>
      </section>

    </div>
  </div>
</div>

<?php
public_shell_end();
