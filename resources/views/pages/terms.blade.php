<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms and Conditions - Domain Monitor Pro</title>
    <link rel="icon" href="{{ asset('img/2.jpg') }}" type="image/x-icon">
    <style>
        :root {
            --primary: #1a1a2e;
            --secondary: #e94560;
            --background: #0a0a14;
            --surface: #12121e;
            --text: #eaeaea;
            --text-muted: #a0a0a0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: var(--background);
            color: var(--text);
            line-height: 1.8;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 60px 24px;
        }

        .header {
            text-align: center;
            margin-bottom: 48px;
            padding-bottom: 32px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logo {
            display: inline-block;
            margin-bottom: 24px;
        }

        .logo img {
            height: 48px;
            border-radius: 8px;
        }

        h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .last-updated {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 40px 0 16px;
            color: var(--secondary);
        }

        h3 {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 24px 0 12px;
        }

        p {
            color: var(--text-muted);
            margin-bottom: 16px;
        }

        ul, ol {
            color: var(--text-muted);
            margin: 16px 0;
            padding-left: 24px;
        }

        li {
            margin-bottom: 8px;
        }

        .highlight {
            background: var(--surface);
            border-left: 4px solid var(--secondary);
            padding: 16px 24px;
            margin: 24px 0;
            border-radius: 0 8px 8px 0;
        }

        .highlight p {
            margin: 0;
            color: var(--text);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--secondary);
            text-decoration: none;
            margin-top: 48px;
            padding: 12px 24px;
            background: rgba(233, 69, 96, 0.1);
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            background: rgba(233, 69, 96, 0.2);
        }

        .footer {
            text-align: center;
            margin-top: 64px;
            padding-top: 32px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 2rem;
            }

            h2 {
                font-size: 1.3rem;
            }

            .container {
                padding: 40px 16px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="/landing.html" class="logo">
                <img src="{{ asset('img/logo.png') }}" alt="Domain Monitor Pro">
            </a>
            <h1>Terms and Conditions</h1>
            <p class="last-updated">Last updated: January 15, 2026</p>
        </div>

        <div class="highlight">
            <p>Please read these Terms and Conditions carefully before using our Service. By accessing or using the Service, you agree to be bound by these Terms.</p>
        </div>

        <h2>1. Acceptance of Terms</h2>
        <p>By creating an account or using Domain Monitor Pro ("the Service"), you agree to be bound by these Terms and Conditions and our Privacy Policy. If you do not agree to these terms, please do not use our Service.</p>

        <h2>2. Description of Service</h2>
        <p>Domain Monitor Pro provides domain monitoring and SSL certificate tracking services. Our services include:</p>
        <ul>
            <li>Real-time domain availability monitoring</li>
            <li>SSL certificate validity checking</li>
            <li>Uptime alerts and notifications</li>
            <li>Historical uptime data and reporting</li>
            <li>Bulk domain import and management</li>
        </ul>

        <h2>3. Account Registration</h2>
        <p>To use certain features of the Service, you must register for an account. When registering, you agree to:</p>
        <ul>
            <li>Provide accurate, current, and complete information</li>
            <li>Maintain and promptly update your account information</li>
            <li>Maintain the security of your password and account</li>
            <li>Accept responsibility for all activities under your account</li>
            <li>Notify us immediately of any unauthorized use of your account</li>
        </ul>

        <h2>4. Subscription Plans and Payment</h2>
        <h3>4.1 Free Plan</h3>
        <p>We offer a free plan with limited features. The free plan includes monitoring for up to 50 domains with a 60-minute check interval.</p>

        <h3>4.2 Paid Plans</h3>
        <p>Paid plans (Pro and Max) offer additional features including:</p>
        <ul>
            <li>Higher domain limits</li>
            <li>Faster check intervals</li>
            <li>SSL certificate monitoring</li>
            <li>Advanced notification options</li>
            <li>Extended history retention</li>
        </ul>

        <h3>4.3 Billing</h3>
        <p>Paid subscriptions are billed monthly in advance. All fees are non-refundable except as required by law or as explicitly stated in these Terms.</p>

        <h2>5. Acceptable Use Policy</h2>
        <p>You agree not to use the Service to:</p>
        <ul>
            <li>Violate any applicable laws or regulations</li>
            <li>Monitor domains you do not own or have authorization to monitor</li>
            <li>Attempt to gain unauthorized access to our systems</li>
            <li>Interfere with or disrupt the Service</li>
            <li>Use the Service for any illegal or unauthorized purpose</li>
            <li>Transmit viruses, malware, or other harmful code</li>
        </ul>

        <h2>6. Service Availability</h2>
        <p>While we strive to maintain 99.9% uptime, we do not guarantee uninterrupted service. The Service may be temporarily unavailable due to:</p>
        <ul>
            <li>Scheduled maintenance (we will provide advance notice when possible)</li>
            <li>Emergency maintenance</li>
            <li>Factors beyond our control</li>
        </ul>

        <h2>7. Data and Privacy</h2>
        <p>Your use of the Service is also governed by our Privacy Policy. By using the Service, you consent to our collection and use of your data as described in the Privacy Policy.</p>

        <h2>8. Intellectual Property</h2>
        <p>The Service and its original content, features, and functionality are owned by Domain Monitor Pro and are protected by international copyright, trademark, and other intellectual property laws.</p>

        <h2>9. Limitation of Liability</h2>
        <div class="highlight">
            <p>To the maximum extent permitted by law, Domain Monitor Pro shall not be liable for any indirect, incidental, special, consequential, or punitive damages, including loss of profits, data, or business opportunities.</p>
        </div>

        <h2>10. Disclaimer of Warranties</h2>
        <p>The Service is provided "as is" and "as available" without warranties of any kind, either express or implied, including but not limited to implied warranties of merchantability, fitness for a particular purpose, and non-infringement.</p>

        <h2>11. Termination</h2>
        <p>We may terminate or suspend your account and access to the Service immediately, without prior notice, for conduct that we believe:</p>
        <ul>
            <li>Violates these Terms</li>
            <li>Is harmful to other users, us, or third parties</li>
            <li>Is fraudulent or illegal</li>
        </ul>

        <h2>12. Changes to Terms</h2>
        <p>We reserve the right to modify these Terms at any time. We will notify users of material changes via email or through the Service. Continued use of the Service after changes constitutes acceptance of the new Terms.</p>

        <h2>13. Governing Law</h2>
        <p>These Terms shall be governed by and construed in accordance with the laws of the United Arab Emirates, without regard to its conflict of law provisions.</p>

        <h2>14. Contact Information</h2>
        <p>For questions about these Terms, please contact us at:</p>
        <ul>
            <li>Email: <span id="contact-email"></span></li>
            <li>Phone: +971 50 586 6567</li>
            <li>Address: Dubai Internet City, Building 12, Dubai, UAE</li>
        </ul>

        <a href="/register" class="back-link">← Back to Registration</a>

        <div class="footer">
            <p>&copy; 2026 Domain Monitor Pro. All rights reserved.</p>
        </div>
    </div>

    <script>
        document.getElementById('contact-email').textContent = 'legal@' + window.location.hostname;
    </script>
</body>
</html>
