<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

require_login();

$userId = current_user_id();
$clubId = (int)($_GET['club_id'] ?? 0);

if (!$clubId) {
  http_response_code(400);
  exit('Club ID required');
}

$stmt = $pdo->prepare("SELECT * FROM clubs WHERE id = ?");
$stmt->execute([$clubId]);
$club = $stmt->fetch();

if (!$club) {
  http_response_code(404);
  exit('Club not found');
}

$stmt = $pdo->prepare("SELECT admin_role FROM club_admins WHERE club_id = ? AND user_id = ?");
$stmt->execute([$clubId, $userId]);
$adminRow = $stmt->fetch();

$stmt = $pdo->prepare("SELECT committee_role FROM club_members WHERE club_id = ? AND user_id = ? AND membership_status = 'active'");
$stmt->execute([$clubId, $userId]);
$memberRow = $stmt->fetch();
$committeeRole = $memberRow['committee_role'] ?? null;

$canViewDocuments = $adminRow || in_array($committeeRole, ['chairperson', 'secretary', 'treasurer', 'pro', 'cwo', 'competition_secretary', 'committee']);

if (!$canViewDocuments) {
  http_response_code(403);
  exit('Only committee members can access club documents');
}

$activeTab = $_GET['tab'] ?? 'templates';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Club Documents - <?= e($club['name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    .template-card { transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; }
    .template-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
    .template-content { display: none; }
    .template-content.show { display: block; }
    .copy-btn { position: absolute; top: 10px; right: 10px; }
    pre { background: #f8f9fa; padding: 1rem; border-radius: 8px; font-size: 0.85rem; white-space: pre-wrap; max-height: 400px; overflow-y: auto; }
  </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="/">Angling Club Manager</a>
    <div class="ms-auto">
      <a class="btn btn-outline-light btn-sm" href="/public/dashboard.php">Dashboard</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2><i class="bi bi-file-earmark-text me-2"></i>Club Documents & Templates</h2>
      <p class="text-muted mb-0"><?= e($club['name']) ?></p>
    </div>
    <a href="/public/club.php?slug=<?= e($club['slug']) ?>" class="btn btn-outline-secondary">Back to Club</a>
  </div>
  
  <ul class="nav nav-tabs mb-4">
    <li class="nav-item">
      <a class="nav-link <?= $activeTab === 'templates' ? 'active' : '' ?>" href="?club_id=<?= $clubId ?>&tab=templates">
        <i class="bi bi-file-earmark-text me-1"></i>Document Templates
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $activeTab === 'external' ? 'active' : '' ?>" href="?club_id=<?= $clubId ?>&tab=external">
        <i class="bi bi-link-45deg me-1"></i>Official Resources
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $activeTab === 'checklist' ? 'active' : '' ?>" href="?club_id=<?= $clubId ?>&tab=checklist">
        <i class="bi bi-check2-square me-1"></i>Document Checklist
      </a>
    </li>
  </ul>
  
  <?php if ($activeTab === 'templates'): ?>
    <div class="alert alert-info mb-4">
      <i class="bi bi-info-circle me-2"></i>
      <strong>How to use:</strong> Click on a template to expand it, then copy the text and paste it into your club's policies section or a word processor to customize.
    </div>
    
    <div class="row g-4">
      <div class="col-12">
        <div class="card template-card" onclick="toggleTemplate('constitution')">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h5 class="mb-1"><i class="bi bi-file-earmark-ruled text-primary me-2"></i>Club Constitution Template</h5>
                <p class="text-muted mb-0 small">The foundational document governing your club's structure and operations</p>
              </div>
              <i class="bi bi-chevron-down" id="constitution-icon"></i>
            </div>
          </div>
        </div>
        <div class="template-content card card-body mt-2" id="constitution-content">
          <div class="position-relative">
            <button class="btn btn-sm btn-outline-primary copy-btn" onclick="copyTemplate('constitution-text')">
              <i class="bi bi-clipboard me-1"></i>Copy
            </button>
            <pre id="constitution-text">CONSTITUTION OF [CLUB NAME] ANGLING CLUB

1. NAME
The name of the club shall be [CLUB NAME] Angling Club (hereinafter referred to as "the Club").

2. OBJECTIVES
The objectives of the Club shall be:
a) To promote the sport of angling in all its forms
b) To provide facilities and opportunities for members to participate in angling
c) To foster good sportsmanship and ethical fishing practices
d) To promote conservation of fish stocks and aquatic environments
e) To organise competitions and social events for members
f) To affiliate with relevant national angling bodies

3. MEMBERSHIP
3.1 Classes of Membership:
- Full Member: Adult anglers (18 years and over)
- Junior Member: Anglers under 18 years of age
- Senior Member: Members aged 65 years and over
- Honorary Member: As elected by the Committee
- Associate Member: Non-fishing members

3.2 Application for membership shall be made on the official application form and approved by the Committee.

3.3 All members must hold a valid IFI rod licence where required by law.

3.4 The Committee reserves the right to refuse or revoke membership.

4. SUBSCRIPTIONS
4.1 Annual subscription rates shall be determined at the Annual General Meeting.

4.2 Subscriptions are due on [DATE] each year and must be paid within 30 days.

4.3 Members whose subscriptions are unpaid after 30 days shall be deemed to have resigned.

5. OFFICERS AND COMMITTEE
5.1 The Officers of the Club shall be:
- Chairperson
- Vice-Chairperson
- Secretary
- Treasurer
- Public Relations Officer (PRO)
- Children's Welfare Officer

5.2 The Committee shall consist of the Officers plus [NUMBER] ordinary members.

5.3 Officers and Committee members shall be elected at the Annual General Meeting.

5.4 The Committee shall meet at least [NUMBER] times per year.

5.5 A quorum for Committee meetings shall be [NUMBER] members including at least two Officers.

6. ANNUAL GENERAL MEETING
6.1 The AGM shall be held each year in [MONTH].

6.2 Notice of the AGM shall be given to all members at least 21 days in advance.

6.3 The agenda shall include:
- Minutes of the previous AGM
- Chairperson's Report
- Secretary's Report
- Treasurer's Report and Accounts
- Election of Officers and Committee
- Motions submitted by members
- Setting of subscription rates
- Any Other Business

6.4 Motions for the AGM must be submitted in writing to the Secretary at least 14 days before the meeting.

6.5 A quorum for the AGM shall be [NUMBER] members or 25% of membership, whichever is less.

7. EXTRAORDINARY GENERAL MEETINGS
7.1 An EGM may be called by the Committee or upon written request of [NUMBER] members.

7.2 Notice of an EGM shall be given at least 14 days in advance, stating the business to be conducted.

8. VOTING
8.1 All Full, Senior, and Honorary members in good standing shall have one vote.

8.2 Junior members shall not have voting rights.

8.3 Decisions shall be by simple majority unless otherwise specified.

8.4 Constitutional amendments require a two-thirds majority.

8.5 The Chairperson shall have a casting vote in the event of a tie.

9. FINANCES
9.1 The Treasurer shall maintain accurate records of all income and expenditure.

9.2 The Club's financial year shall run from [DATE] to [DATE].

9.3 Annual accounts shall be presented at the AGM.

9.4 All cheques/payments over €[AMOUNT] shall require two signatures from authorised signatories.

9.5 The Committee shall appoint an auditor or independent examiner annually.

10. CLUB RULES
10.1 The Committee shall have the power to make, amend, and enforce rules for the conduct of angling and use of club waters/facilities.

10.2 All members shall abide by the Club rules, this Constitution, and the rules of any national body with which the Club is affiliated.

11. DISCIPLINE
11.1 The Committee shall have the power to suspend or expel any member for breach of rules or conduct unbecoming.

11.2 Before any disciplinary action, the member shall be given the opportunity to be heard.

11.3 Appeals against disciplinary decisions may be made in writing to the Committee within 14 days.

12. SAFEGUARDING
12.1 The Club is committed to safeguarding children and vulnerable adults.

12.2 The Club shall maintain appropriate safeguarding policies and procedures.

12.3 All persons working with children shall be Garda vetted.

13. DATA PROTECTION
13.1 The Club shall comply with all applicable data protection legislation.

13.2 Member data shall be used only for Club purposes and shall not be shared with third parties without consent.

14. DISSOLUTION
14.1 The Club may be dissolved by a resolution passed by a three-quarters majority at a General Meeting called for that purpose.

14.2 Upon dissolution, any remaining assets after settling debts shall be donated to [SPECIFY - e.g., a charitable angling organisation].

15. AMENDMENTS
15.1 This Constitution may be amended by a two-thirds majority vote at an AGM or EGM.

15.2 Proposed amendments must be submitted in writing at least 21 days before the meeting.

Adopted at the [Annual/Inaugural] General Meeting held on [DATE].

Signed:
_________________________ (Chairperson)
_________________________ (Secretary)
_________________________ (Date)</pre>
          </div>
        </div>
      </div>
      
      <div class="col-12">
        <div class="card template-card" onclick="toggleTemplate('membership')">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h5 class="mb-1"><i class="bi bi-person-plus text-success me-2"></i>Membership Application Form</h5>
                <p class="text-muted mb-0 small">Template for new member applications</p>
              </div>
              <i class="bi bi-chevron-down" id="membership-icon"></i>
            </div>
          </div>
        </div>
        <div class="template-content card card-body mt-2" id="membership-content">
          <div class="position-relative">
            <button class="btn btn-sm btn-outline-primary copy-btn" onclick="copyTemplate('membership-text')">
              <i class="bi bi-clipboard me-1"></i>Copy
            </button>
            <pre id="membership-text">[CLUB NAME] ANGLING CLUB
MEMBERSHIP APPLICATION FORM

PERSONAL DETAILS
Title: Mr / Mrs / Ms / Other: _______
First Name: ________________________________
Surname: __________________________________
Date of Birth: ____/____/________
Address: __________________________________
         __________________________________
         __________________________________
Eircode: __________________________________
Phone: ____________________________________
Email: ____________________________________

MEMBERSHIP TYPE (please tick one)
[ ] Full Member (18+)          €_____
[ ] Junior Member (under 18)   €_____
[ ] Senior Member (65+)        €_____
[ ] Family Membership          €_____

EMERGENCY CONTACT
Name: ____________________________________
Relationship: _____________________________
Phone: ___________________________________

FISHING EXPERIENCE
Years fishing: ____________________________
Preferred fishing style(s): _______________
_________________________________________

IFI ROD LICENCE
Licence Number: ___________________________
Expiry Date: ____/____/________

DECLARATIONS

I declare that:
1. The information provided is true and accurate
2. I have read and agree to abide by the Club Constitution and Rules
3. I will comply with all fishing regulations and licence requirements
4. I consent to my data being held by the Club for membership purposes

Signature: _______________________________
Date: ____/____/________

FOR JUNIOR MEMBERS (under 18)
Parent/Guardian Name: ____________________
Parent/Guardian Signature: _______________
Parent/Guardian Phone: ___________________

OFFICE USE ONLY
Date Received: ___________________________
Membership Number: _______________________
Payment Received: ________________________
Approved by Committee: ___________________
Date: ___________________________________</pre>
          </div>
        </div>
      </div>
      
      <div class="col-12">
        <div class="card template-card" onclick="toggleTemplate('safeguarding')">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h5 class="mb-1"><i class="bi bi-shield-check text-danger me-2"></i>Safeguarding Policy Template</h5>
                <p class="text-muted mb-0 small">Child and vulnerable adult protection policy</p>
              </div>
              <i class="bi bi-chevron-down" id="safeguarding-icon"></i>
            </div>
          </div>
        </div>
        <div class="template-content card card-body mt-2" id="safeguarding-content">
          <div class="position-relative">
            <button class="btn btn-sm btn-outline-primary copy-btn" onclick="copyTemplate('safeguarding-text')">
              <i class="bi bi-clipboard me-1"></i>Copy
            </button>
            <pre id="safeguarding-text">[CLUB NAME] ANGLING CLUB
SAFEGUARDING POLICY

1. POLICY STATEMENT
[Club Name] Angling Club is committed to safeguarding the welfare of all children and vulnerable adults involved in our activities. We recognise that everyone has the right to participate in sport in a safe and enjoyable environment.

This policy applies to all members, volunteers, coaches, and anyone involved in club activities.

2. DEFINITIONS
Child: Any person under the age of 18 years
Vulnerable Adult: A person over 18 who may be unable to protect themselves from harm or exploitation

3. KEY PRINCIPLES
- The welfare of children and vulnerable adults is paramount
- All children and vulnerable adults have the right to protection from abuse
- All suspicions and allegations of abuse will be taken seriously
- Working in partnership with parents/guardians is essential
- All those involved in angling must operate within an accepted ethical framework

4. CHILDREN'S WELFARE OFFICER
The Club has appointed a Children's Welfare Officer (CWO):
Name: ____________________________________
Contact: _________________________________

The CWO is responsible for:
- Promoting safeguarding within the club
- Being the first point of contact for safeguarding concerns
- Ensuring vetting requirements are met
- Liaising with external agencies as required
- Maintaining safeguarding records

5. RECRUITMENT AND VETTING
- All persons working with children must be Garda vetted through the National Vetting Bureau
- Vetting is mandatory under the National Vetting Bureau Acts 2012-2016
- References will be sought for those in positions of responsibility
- Relevant training must be completed

6. CODE OF CONDUCT
All adults must:
- Treat all children with respect and dignity
- Maintain appropriate boundaries
- Never be alone with a child (use open and observable spaces)
- Avoid unnecessary physical contact
- Never use inappropriate language or make suggestive comments
- Report any concerns to the CWO

Children are expected to:
- Treat fellow members with respect
- Follow club rules and safety instructions
- Tell an adult if they are uncomfortable or witness inappropriate behaviour

7. PHOTOGRAPHY AND SOCIAL MEDIA
- Parental consent is required before photographing children
- Images should not identify children by name
- Parents may take photos of their own children only
- No images should be taken in changing areas

8. REPORTING CONCERNS
If you have concerns about a child's safety:
1. Stay calm and listen
2. Do not promise confidentiality
3. Report to the CWO immediately
4. Record what was said/observed
5. Do not investigate yourself

In an emergency, contact:
- Gardai: 999 / 112
- TUSLA: www.tusla.ie

9. CONFIDENTIALITY
Information relating to child protection will be shared only on a "need to know" basis and in accordance with data protection requirements.

10. REVIEW
This policy will be reviewed annually by the Committee.

Adopted: ____/____/________
Review Date: ____/____/________

Signed: _________________________ (Chairperson)
Signed: _________________________ (CWO)</pre>
          </div>
        </div>
      </div>
      
      <div class="col-12">
        <div class="card template-card" onclick="toggleTemplate('consent')">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h5 class="mb-1"><i class="bi bi-person-check text-warning me-2"></i>Junior Parental Consent Form</h5>
                <p class="text-muted mb-0 small">Required consent form for junior members</p>
              </div>
              <i class="bi bi-chevron-down" id="consent-icon"></i>
            </div>
          </div>
        </div>
        <div class="template-content card card-body mt-2" id="consent-content">
          <div class="position-relative">
            <button class="btn btn-sm btn-outline-primary copy-btn" onclick="copyTemplate('consent-text')">
              <i class="bi bi-clipboard me-1"></i>Copy
            </button>
            <pre id="consent-text">[CLUB NAME] ANGLING CLUB
PARENTAL/GUARDIAN CONSENT FORM

JUNIOR MEMBER DETAILS
Name: ____________________________________
Date of Birth: ____/____/________
Address: __________________________________
         __________________________________

PARENT/GUARDIAN DETAILS
Name: ____________________________________
Relationship to Junior: ___________________
Address (if different): ___________________
         __________________________________
Phone: ___________________________________
Email: ___________________________________

EMERGENCY CONTACT (if different from above)
Name: ____________________________________
Phone: ___________________________________

MEDICAL INFORMATION
Does your child have any medical conditions we should be aware of?
[ ] No  [ ] Yes - please specify:
__________________________________________
__________________________________________

Does your child take any regular medication?
[ ] No  [ ] Yes - please specify:
__________________________________________

Does your child have any allergies?
[ ] No  [ ] Yes - please specify:
__________________________________________

Doctor's Name: ____________________________
Doctor's Phone: ___________________________

CONSENT

I, the undersigned, being the parent/guardian of the above-named child:

1. MEMBERSHIP CONSENT
[ ] I consent to my child becoming a member of [Club Name] Angling Club

2. ACTIVITY CONSENT
[ ] I consent to my child participating in club activities, competitions, and events

3. SUPERVISION CONSENT
[ ] I understand that my child will be supervised by club officials during organised activities
[ ] I will ensure my child is accompanied by a responsible adult at all times during club activities (delete if not applicable)

4. PHOTOGRAPHY CONSENT
[ ] I consent to photographs/videos of my child being taken for club purposes
[ ] I consent to images being used on the club website/social media
[ ] I DO NOT consent to photography of my child

5. TRANSPORT CONSENT
[ ] I consent to my child travelling in vehicles driven by club officials to/from events
[ ] I DO NOT consent to transport by club officials

6. MEDICAL CONSENT
[ ] In an emergency, I authorise club officials to seek medical treatment for my child if I cannot be contacted

7. ACKNOWLEDGEMENTS
[ ] I have read and understood the Club's Safeguarding Policy
[ ] I have read and understood the Club's Rules
[ ] I will ensure my child understands and follows the Club's Code of Conduct

DECLARATION
I confirm that the information provided is accurate and I will inform the club of any changes.

Parent/Guardian Signature: _________________
Print Name: ______________________________
Date: ____/____/________

This form is valid for one year and must be renewed annually.</pre>
          </div>
        </div>
      </div>
      
      <div class="col-12">
        <div class="card template-card" onclick="toggleTemplate('conduct')">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h5 class="mb-1"><i class="bi bi-journal-check text-info me-2"></i>Code of Conduct Template</h5>
                <p class="text-muted mb-0 small">Expected behaviour for all club members</p>
              </div>
              <i class="bi bi-chevron-down" id="conduct-icon"></i>
            </div>
          </div>
        </div>
        <div class="template-content card card-body mt-2" id="conduct-content">
          <div class="position-relative">
            <button class="btn btn-sm btn-outline-primary copy-btn" onclick="copyTemplate('conduct-text')">
              <i class="bi bi-clipboard me-1"></i>Copy
            </button>
            <pre id="conduct-text">[CLUB NAME] ANGLING CLUB
CODE OF CONDUCT

All members are expected to conduct themselves in a manner that reflects positively on the Club and the sport of angling.

1. GENERAL CONDUCT
- Treat all fellow members, officials, and the public with respect and courtesy
- Respect the environment and leave all fishing locations as you found them
- Follow all club rules and the instructions of club officials
- Report any incidents, accidents, or concerns to club officials promptly
- Do not engage in bullying, harassment, or discrimination of any kind

2. FISHING CONDUCT
- Hold a valid IFI rod licence and any required permits
- Practice catch and release where appropriate
- Handle fish with care and return them safely to the water
- Use appropriate tackle and methods as specified by club rules
- Do not fish in closed seasons or take undersized fish
- Report any fish kills or pollution to the appropriate authorities
- Leave swims and platforms in good condition

3. COMPETITION CONDUCT
- Fish fairly and within the rules of the competition
- Accept the decisions of match officials
- Congratulate winners and accept defeat graciously
- Do not interfere with other anglers' swims or equipment
- Weigh-in catches accurately and honestly

4. SAFETY
- Wear appropriate clothing and footwear
- Take care on wet or slippery banks
- Be aware of overhead power lines when using carbon fibre rods
- Do not fish alone in remote locations without informing someone
- Be aware of weather conditions and water levels
- Know the location of the nearest telephone and emergency services

5. ENVIRONMENTAL RESPONSIBILITY
- Take all litter home, including discarded line and hooks
- Use barbless hooks where required
- Do not introduce non-native species or transfer fish between waters
- Report invasive species sightings
- Respect wildlife and nesting birds

6. CLUB WATERS/VENUES
- Only fish on club waters if you are a paid-up member
- Do not allow non-members to fish on club waters without permission
- Report any damage to banks, platforms, or equipment
- Close gates and respect landowners' property
- Park only in designated areas

7. ALCOHOL AND DRUGS
- Do not fish while under the influence of alcohol or drugs
- Alcohol consumption at club events should be responsible

8. SOCIAL MEDIA
- Do not post content that brings the Club into disrepute
- Respect the privacy of fellow members
- Do not share identifying information about club waters without permission

9. BREACH OF CODE
Failure to comply with this Code of Conduct may result in:
- Verbal warning
- Written warning
- Suspension of membership
- Expulsion from the Club

The Committee's decision on disciplinary matters is final.

I have read, understood, and agree to abide by this Code of Conduct.

Member Signature: _________________________
Print Name: ______________________________
Date: ____/____/________</pre>
          </div>
        </div>
      </div>
      
      <div class="col-12">
        <div class="card template-card" onclick="toggleTemplate('privacy')">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h5 class="mb-1"><i class="bi bi-lock text-secondary me-2"></i>Privacy Policy Template</h5>
                <p class="text-muted mb-0 small">GDPR-compliant data protection policy</p>
              </div>
              <i class="bi bi-chevron-down" id="privacy-icon"></i>
            </div>
          </div>
        </div>
        <div class="template-content card card-body mt-2" id="privacy-content">
          <div class="position-relative">
            <button class="btn btn-sm btn-outline-primary copy-btn" onclick="copyTemplate('privacy-text')">
              <i class="bi bi-clipboard me-1"></i>Copy
            </button>
            <pre id="privacy-text">[CLUB NAME] ANGLING CLUB
PRIVACY POLICY

1. INTRODUCTION
[Club Name] Angling Club ("the Club") is committed to protecting the privacy and personal data of our members. This policy explains how we collect, use, and protect your personal information in accordance with the General Data Protection Regulation (GDPR) and the Data Protection Acts 1988-2018.

2. DATA CONTROLLER
The Club is the data controller. Our contact details are:
Address: [Club Address]
Email: [Club Email]
Data Protection Contact: [Secretary/Designated Person]

3. DATA WE COLLECT
We collect the following personal data:
- Name and contact details (address, phone, email)
- Date of birth
- Emergency contact details
- Medical information (for safety purposes)
- Rod licence details
- Photographs (with consent)
- Payment information
- Competition results and catch records

4. HOW WE USE YOUR DATA
We use your personal data to:
- Administer your membership
- Communicate club news, events, and activities
- Organise competitions and maintain records
- Ensure your safety during club activities
- Comply with legal obligations
- Affiliate with national angling bodies

5. LEGAL BASIS FOR PROCESSING
We process your data based on:
- Your consent (which you may withdraw at any time)
- The performance of our membership contract with you
- Our legitimate interests in running the Club
- Legal obligations (e.g., safeguarding, vetting)

6. DATA SHARING
We may share your data with:
- National angling bodies we are affiliated with
- Insurance providers (for coverage purposes)
- Competition organisers (name and results only)
- Emergency services (in case of emergency)

We will not sell your data to third parties.

7. DATA RETENTION
We retain your data for:
- Active members: For the duration of membership plus 2 years
- Former members: 2 years after membership ends
- Competition records: 7 years (for historical records)
- Safeguarding records: As required by law

8. DATA SECURITY
We protect your data by:
- Storing physical records securely
- Using password protection for electronic records
- Limiting access to those who need it
- Training committee members on data protection

9. YOUR RIGHTS
Under GDPR, you have the right to:
- Access your personal data
- Correct inaccurate data
- Request deletion of your data
- Restrict or object to processing
- Data portability
- Withdraw consent

To exercise these rights, contact the Club Secretary.

10. CHILDREN'S DATA
For members under 18, we require parental/guardian consent before collecting personal data. Parents/guardians may access and manage their child's data.

11. COOKIES AND WEBSITE
If the Club operates a website, we may use cookies to improve your experience. See our Cookie Policy for details.

12. CHANGES TO THIS POLICY
We may update this policy from time to time. Members will be notified of significant changes.

13. COMPLAINTS
If you are unhappy with how we handle your data, please contact us first. You also have the right to complain to the Data Protection Commission:
Website: www.dataprotection.ie

Last Updated: [DATE]</pre>
          </div>
        </div>
      </div>
    </div>
    
  <?php elseif ($activeTab === 'external'): ?>
    <div class="row g-4">
      <div class="col-md-6">
        <div class="card h-100">
          <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="bi bi-file-pdf me-2"></i>NCFFI Resources</h5>
          </div>
          <div class="card-body">
            <a href="https://www.ncffi.ie/club-documentation/" target="_blank" class="d-block p-2 border-bottom">
              <i class="bi bi-box-arrow-up-right me-2"></i>Club Documentation
              <small class="d-block text-muted">Official forms and templates</small>
            </a>
            <a href="https://www.ncffi.ie/wp-content/uploads/2021/12/NCFFI-Safeguarding-Policy-Guide-21.pdf" target="_blank" class="d-block p-2 border-bottom">
              <i class="bi bi-file-pdf me-2 text-danger"></i>Safeguarding Policy & Guide
              <small class="d-block text-muted">Child protection guidance</small>
            </a>
            <a href="https://www.ncffi.ie/wp-content/uploads/2022/12/NCFFI-Codes-of-Conduct.pdf" target="_blank" class="d-block p-2 border-bottom">
              <i class="bi bi-file-pdf me-2 text-danger"></i>Codes of Conduct
              <small class="d-block text-muted">Behaviour expectations</small>
            </a>
            <a href="https://www.ncffi.ie/wp-content/uploads/2021/12/NCFFI-Anti-Bullying.pdf" target="_blank" class="d-block p-2 border-bottom">
              <i class="bi bi-file-pdf me-2 text-danger"></i>Anti-Bullying Policy
              <small class="d-block text-muted">Bullying prevention</small>
            </a>
            <a href="https://www.ncffi.ie/wp-content/uploads/2021/12/NCFFI-Filming-and-Photography-Policy.pdf" target="_blank" class="d-block p-2 border-bottom">
              <i class="bi bi-file-pdf me-2 text-danger"></i>Filming & Photography Policy
              <small class="d-block text-muted">Image consent guidance</small>
            </a>
            <a href="https://www.ncffi.ie/wp-content/uploads/2020/11/NVB1-Form-NCFFI.pdf" target="_blank" class="d-block p-2">
              <i class="bi bi-file-pdf me-2 text-danger"></i>Vetting Form (NVB1)
              <small class="d-block text-muted">Garda vetting application</small>
            </a>
          </div>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="card h-100">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-building me-2"></i>Sport Ireland Resources</h5>
          </div>
          <div class="card-body">
            <a href="https://www.sportireland.ie/GovernanceCode" target="_blank" class="d-block p-2 border-bottom">
              <i class="bi bi-box-arrow-up-right me-2"></i>Governance Code for Sport
              <small class="d-block text-muted">Official governance guidance</small>
            </a>
            <a href="https://www.sportireland.ie/news/writing-a-constitution" target="_blank" class="d-block p-2 border-bottom">
              <i class="bi bi-box-arrow-up-right me-2"></i>Writing a Constitution
              <small class="d-block text-muted">Constitution guidance</small>
            </a>
            <a href="https://www.sportireland.ie/GovernanceCode/Resources" target="_blank" class="d-block p-2 border-bottom">
              <i class="bi bi-box-arrow-up-right me-2"></i>Governance Resources
              <small class="d-block text-muted">Templates and tools</small>
            </a>
            <a href="https://www.sportireland.ie/GovernanceCode/Resources/finance-governance" target="_blank" class="d-block p-2">
              <i class="bi bi-box-arrow-up-right me-2"></i>Finance Governance
              <small class="d-block text-muted">Financial management best practice</small>
            </a>
          </div>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="card h-100">
          <div class="card-header bg-danger text-white">
            <h5 class="mb-0"><i class="bi bi-shield-check me-2"></i>Safeguarding Resources</h5>
          </div>
          <div class="card-body">
            <a href="https://www.tusla.ie/children-first/" target="_blank" class="d-block p-2 border-bottom">
              <i class="bi bi-box-arrow-up-right me-2"></i>TUSLA Children First
              <small class="d-block text-muted">National child protection guidance</small>
            </a>
            <a href="https://vetting.garda.ie/" target="_blank" class="d-block p-2 border-bottom">
              <i class="bi bi-box-arrow-up-right me-2"></i>Garda Vetting Portal
              <small class="d-block text-muted">National Vetting Bureau</small>
            </a>
            <a href="https://www.ncffi.ie/wp-content/uploads/2019/11/Safeguarding-Guidance-for-Children-and-Young-People-in-Sport.pdf" target="_blank" class="d-block p-2">
              <i class="bi bi-file-pdf me-2 text-danger"></i>Safeguarding in Sport Guide
              <small class="d-block text-muted">Sport Ireland/NI guidance</small>
            </a>
          </div>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="card h-100">
          <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="bi bi-water me-2"></i>Fisheries Resources</h5>
          </div>
          <div class="card-body">
            <a href="https://www.fisheriesireland.ie/licences" target="_blank" class="d-block p-2 border-bottom">
              <i class="bi bi-box-arrow-up-right me-2"></i>IFI Rod Licences
              <small class="d-block text-muted">Licence information and purchase</small>
            </a>
            <a href="https://www.fisheriesireland.ie/regulations" target="_blank" class="d-block p-2 border-bottom">
              <i class="bi bi-box-arrow-up-right me-2"></i>Fishing Regulations
              <small class="d-block text-muted">Seasons, limits, and rules</small>
            </a>
            <a href="https://specimenfish.ie/" target="_blank" class="d-block p-2">
              <i class="bi bi-box-arrow-up-right me-2"></i>Irish Specimen Fish Committee
              <small class="d-block text-muted">Specimen fish records</small>
            </a>
          </div>
        </div>
      </div>
    </div>
    
  <?php elseif ($activeTab === 'checklist'): ?>
    <div class="card">
      <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-check2-square me-2"></i>Essential Club Documents Checklist</h5>
      </div>
      <div class="card-body">
        <p class="text-muted">Use this checklist to ensure your club has all the essential documentation in place.</p>
        
        <h6 class="mt-4 text-primary"><i class="bi bi-file-earmark-ruled me-2"></i>Governance Documents</h6>
        <div class="ms-3">
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="doc1">
            <label class="form-check-label" for="doc1">
              <strong>Club Constitution</strong>
              <small class="d-block text-muted">The foundational rules governing your club</small>
            </label>
          </div>
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="doc2">
            <label class="form-check-label" for="doc2">
              <strong>Club Rules</strong>
              <small class="d-block text-muted">Day-to-day fishing rules and regulations</small>
            </label>
          </div>
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="doc3">
            <label class="form-check-label" for="doc3">
              <strong>Code of Conduct</strong>
              <small class="d-block text-muted">Expected behaviour for all members</small>
            </label>
          </div>
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="doc4">
            <label class="form-check-label" for="doc4">
              <strong>Committee Roles Description</strong>
              <small class="d-block text-muted">Responsibilities of each officer position</small>
            </label>
          </div>
        </div>
        
        <h6 class="mt-4 text-success"><i class="bi bi-person-plus me-2"></i>Membership Documents</h6>
        <div class="ms-3">
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="doc5">
            <label class="form-check-label" for="doc5">
              <strong>Membership Application Form</strong>
              <small class="d-block text-muted">Form for new members to complete</small>
            </label>
          </div>
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="doc6">
            <label class="form-check-label" for="doc6">
              <strong>Membership Terms & Conditions</strong>
              <small class="d-block text-muted">Terms members agree to when joining</small>
            </label>
          </div>
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="doc7">
            <label class="form-check-label" for="doc7">
              <strong>Guest/Day Ticket Policy</strong>
              <small class="d-block text-muted">Rules for non-member visitors</small>
            </label>
          </div>
        </div>
        
        <h6 class="mt-4 text-danger"><i class="bi bi-shield-check me-2"></i>Safeguarding Documents (Required if you have junior members)</h6>
        <div class="ms-3">
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="doc8">
            <label class="form-check-label" for="doc8">
              <strong>Safeguarding Policy</strong>
              <small class="d-block text-muted">Child and vulnerable adult protection policy</small>
            </label>
          </div>
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="doc9">
            <label class="form-check-label" for="doc9">
              <strong>Parental Consent Form</strong>
              <small class="d-block text-muted">Consent for junior members</small>
            </label>
          </div>
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="doc10">
            <label class="form-check-label" for="doc10">
              <strong>Anti-Bullying Policy</strong>
              <small class="d-block text-muted">Prevention and response to bullying</small>
            </label>
          </div>
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="doc11">
            <label class="form-check-label" for="doc11">
              <strong>Photography Policy</strong>
              <small class="d-block text-muted">Rules for photographing members</small>
            </label>
          </div>
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="doc12">
            <label class="form-check-label" for="doc12">
              <strong>Vetting Records</strong>
              <small class="d-block text-muted">Garda vetting for all adults working with children</small>
            </label>
          </div>
        </div>
        
        <h6 class="mt-4 text-warning"><i class="bi bi-cash-stack me-2"></i>Financial Documents</h6>
        <div class="ms-3">
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="doc13">
            <label class="form-check-label" for="doc13">
              <strong>Financial Procedures</strong>
              <small class="d-block text-muted">How finances are managed and controlled</small>
            </label>
          </div>
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="doc14">
            <label class="form-check-label" for="doc14">
              <strong>Expense Claim Form</strong>
              <small class="d-block text-muted">For members to claim approved expenses</small>
            </label>
          </div>
        </div>
        
        <h6 class="mt-4 text-secondary"><i class="bi bi-lock me-2"></i>Data Protection Documents</h6>
        <div class="ms-3">
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="doc15">
            <label class="form-check-label" for="doc15">
              <strong>Privacy Policy</strong>
              <small class="d-block text-muted">How member data is collected and used</small>
            </label>
          </div>
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="doc16">
            <label class="form-check-label" for="doc16">
              <strong>Data Retention Schedule</strong>
              <small class="d-block text-muted">How long records are kept</small>
            </label>
          </div>
        </div>
        
        <div class="alert alert-success mt-4">
          <i class="bi bi-lightbulb me-2"></i>
          <strong>Tip:</strong> You can use the templates in the "Document Templates" tab as starting points for many of these documents. Customize them to suit your club's specific needs.
        </div>
      </div>
    </div>
    
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleTemplate(id) {
  const content = document.getElementById(id + '-content');
  const icon = document.getElementById(id + '-icon');
  content.classList.toggle('show');
  icon.classList.toggle('bi-chevron-down');
  icon.classList.toggle('bi-chevron-up');
}

function copyTemplate(id) {
  const text = document.getElementById(id).innerText;
  navigator.clipboard.writeText(text).then(() => {
    const btn = event.target.closest('button');
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-check me-1"></i>Copied!';
    btn.classList.remove('btn-outline-primary');
    btn.classList.add('btn-success');
    setTimeout(() => {
      btn.innerHTML = originalHtml;
      btn.classList.remove('btn-success');
      btn.classList.add('btn-outline-primary');
    }, 2000);
  });
  event.stopPropagation();
}
</script>
</body>
</html>
