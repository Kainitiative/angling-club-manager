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

$canViewGovernance = $adminRow || in_array($committeeRole, ['chairperson', 'secretary', 'treasurer', 'pro', 'cwo', 'competition_secretary', 'committee']);

if (!$canViewGovernance) {
  http_response_code(403);
  exit('Only committee members can access the Governance Hub');
}

$activeSection = $_GET['section'] ?? 'overview';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Governance Hub - <?= e($club['name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    .principle-card { transition: transform 0.2s, box-shadow 0.2s; }
    .principle-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
    .principle-icon { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; border-radius: 12px; font-size: 1.5rem; }
    .resource-link { display: block; padding: 0.75rem 1rem; border-radius: 8px; text-decoration: none; color: inherit; transition: background-color 0.2s; }
    .resource-link:hover { background-color: #f8f9fa; }
    .role-card { border-left: 4px solid #0d6efd; }
    .checklist-item { padding: 0.5rem 0; border-bottom: 1px solid #eee; }
    .checklist-item:last-child { border-bottom: none; }
    .nav-pills .nav-link.active { background-color: #198754; }
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
      <h2><i class="bi bi-shield-check me-2"></i>Governance Hub</h2>
      <p class="text-muted mb-0"><?= e($club['name']) ?> - Best Practice Guides for Committee Members</p>
    </div>
    <a href="/public/club.php?slug=<?= e($club['slug']) ?>" class="btn btn-outline-secondary">Back to Club</a>
  </div>
  
  <div class="row">
    <div class="col-md-3 mb-4">
      <div class="card">
        <div class="card-header bg-success text-white">
          <strong>Governance Topics</strong>
        </div>
        <div class="list-group list-group-flush">
          <a href="?club_id=<?= $clubId ?>&section=overview" class="list-group-item list-group-item-action <?= $activeSection === 'overview' ? 'active' : '' ?>">
            <i class="bi bi-house me-2"></i>Overview
          </a>
          <a href="?club_id=<?= $clubId ?>&section=principles" class="list-group-item list-group-item-action <?= $activeSection === 'principles' ? 'active' : '' ?>">
            <i class="bi bi-list-check me-2"></i>5 Principles
          </a>
          <a href="?club_id=<?= $clubId ?>&section=roles" class="list-group-item list-group-item-action <?= $activeSection === 'roles' ? 'active' : '' ?>">
            <i class="bi bi-people me-2"></i>Committee Roles
          </a>
          <a href="?club_id=<?= $clubId ?>&section=meetings" class="list-group-item list-group-item-action <?= $activeSection === 'meetings' ? 'active' : '' ?>">
            <i class="bi bi-calendar-event me-2"></i>Meetings & AGM
          </a>
          <a href="?club_id=<?= $clubId ?>&section=safeguarding" class="list-group-item list-group-item-action <?= $activeSection === 'safeguarding' ? 'active' : '' ?>">
            <i class="bi bi-shield-check me-2"></i>Safeguarding
          </a>
          <a href="?club_id=<?= $clubId ?>&section=finance" class="list-group-item list-group-item-action <?= $activeSection === 'finance' ? 'active' : '' ?>">
            <i class="bi bi-cash-stack me-2"></i>Financial Controls
          </a>
          <a href="?club_id=<?= $clubId ?>&section=resources" class="list-group-item list-group-item-action <?= $activeSection === 'resources' ? 'active' : '' ?>">
            <i class="bi bi-link-45deg me-2"></i>External Resources
          </a>
        </div>
      </div>
      
      <div class="card mt-3">
        <div class="card-header bg-white">
          <strong><i class="bi bi-info-circle me-2"></i>About This Guide</strong>
        </div>
        <div class="card-body small text-muted">
          <p>This guide is based on the <strong>Sport Ireland Governance Code for Sport</strong> and best practices from the <strong>NCFFI</strong>.</p>
          <p class="mb-0">All angling clubs affiliated with national bodies should aim to comply with these governance standards.</p>
        </div>
      </div>
    </div>
    
    <div class="col-md-9">
      <?php if ($activeSection === 'overview'): ?>
        <div class="card mb-4">
          <div class="card-body">
            <h4>Welcome to the Governance Hub</h4>
            <p class="lead">Good governance helps your club run effectively, maintain member trust, and meet the standards expected by national bodies like the NCFFI and Sport Ireland.</p>
            <hr>
            <h5>Why Governance Matters</h5>
            <div class="row g-3 mb-4">
              <div class="col-md-4">
                <div class="text-center p-3 bg-light rounded">
                  <i class="bi bi-people text-primary" style="font-size: 2rem;"></i>
                  <h6 class="mt-2">Member Trust</h6>
                  <small class="text-muted">Transparent decisions build confidence</small>
                </div>
              </div>
              <div class="col-md-4">
                <div class="text-center p-3 bg-light rounded">
                  <i class="bi bi-shield-check text-success" style="font-size: 2rem;"></i>
                  <h6 class="mt-2">Legal Compliance</h6>
                  <small class="text-muted">Meet safeguarding and GDPR requirements</small>
                </div>
              </div>
              <div class="col-md-4">
                <div class="text-center p-3 bg-light rounded">
                  <i class="bi bi-graph-up text-info" style="font-size: 2rem;"></i>
                  <h6 class="mt-2">Sustainability</h6>
                  <small class="text-muted">Ensure long-term club success</small>
                </div>
              </div>
            </div>
            
            <h5>Quick Links for Your Committee</h5>
            <div class="row g-2">
              <div class="col-md-6">
                <a href="/public/admin/policies.php?club_id=<?= $clubId ?>" class="resource-link border rounded">
                  <i class="bi bi-file-text text-primary me-2"></i>
                  <strong>Club Policies</strong>
                  <small class="text-muted d-block">Edit constitution, rules, and terms</small>
                </a>
              </div>
              <div class="col-md-6">
                <a href="/public/admin/meetings.php?club_id=<?= $clubId ?>" class="resource-link border rounded">
                  <i class="bi bi-calendar-event text-success me-2"></i>
                  <strong>Meetings</strong>
                  <small class="text-muted d-block">Track meetings and decisions</small>
                </a>
              </div>
              <div class="col-md-6">
                <a href="/public/admin/finances.php?club_id=<?= $clubId ?>" class="resource-link border rounded">
                  <i class="bi bi-cash-stack text-warning me-2"></i>
                  <strong>Finances</strong>
                  <small class="text-muted d-block">Income, expenses, and reports</small>
                </a>
              </div>
              <div class="col-md-6">
                <a href="/public/admin/members.php?club_id=<?= $clubId ?>" class="resource-link border rounded">
                  <i class="bi bi-people text-info me-2"></i>
                  <strong>Members</strong>
                  <small class="text-muted d-block">Manage memberships</small>
                </a>
              </div>
            </div>
          </div>
        </div>
        
      <?php elseif ($activeSection === 'principles'): ?>
        <div class="card mb-4">
          <div class="card-header bg-white">
            <h4 class="mb-0"><i class="bi bi-list-check me-2"></i>The 5 Governance Principles</h4>
          </div>
          <div class="card-body">
            <p>The Sport Ireland Governance Code is built on five core principles. Even small clubs should strive to meet these standards.</p>
          </div>
        </div>
        
        <div class="row g-4">
          <div class="col-md-6">
            <div class="card principle-card h-100">
              <div class="card-body">
                <div class="d-flex align-items-start mb-3">
                  <div class="principle-icon bg-primary text-white me-3">1</div>
                  <div>
                    <h5>Leading Our Organisation</h5>
                    <span class="badge bg-primary">Leadership</span>
                  </div>
                </div>
                <ul class="mb-0">
                  <li>Define your club's purpose and values</li>
                  <li>Have a clear committee structure</li>
                  <li>Ensure the committee takes responsibility for governance</li>
                  <li>Set strategic direction for the club</li>
                  <li>Review performance regularly</li>
                </ul>
              </div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="card principle-card h-100">
              <div class="card-body">
                <div class="d-flex align-items-start mb-3">
                  <div class="principle-icon bg-success text-white me-3">2</div>
                  <div>
                    <h5>Exercising Control</h5>
                    <span class="badge bg-success">Controls</span>
                  </div>
                </div>
                <ul class="mb-0">
                  <li>Implement financial controls and oversight</li>
                  <li>Have risk management processes</li>
                  <li>Maintain proper record-keeping</li>
                  <li>Ensure compliance with legal requirements</li>
                  <li>Protect club assets</li>
                </ul>
              </div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="card principle-card h-100">
              <div class="card-body">
                <div class="d-flex align-items-start mb-3">
                  <div class="principle-icon bg-info text-white me-3">3</div>
                  <div>
                    <h5>Being Transparent</h5>
                    <span class="badge bg-info">Accountability</span>
                  </div>
                </div>
                <ul class="mb-0">
                  <li>Communicate openly with members</li>
                  <li>Publish annual reports and accounts</li>
                  <li>Make decision-making processes clear</li>
                  <li>Hold regular member meetings (AGM)</li>
                  <li>Respond to member queries promptly</li>
                </ul>
              </div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="card principle-card h-100">
              <div class="card-body">
                <div class="d-flex align-items-start mb-3">
                  <div class="principle-icon bg-warning text-dark me-3">4</div>
                  <div>
                    <h5>Working Effectively</h5>
                    <span class="badge bg-warning text-dark">Efficiency</span>
                  </div>
                </div>
                <ul class="mb-0">
                  <li>Hold regular committee meetings with agendas</li>
                  <li>Define clear roles and responsibilities</li>
                  <li>Plan for succession of officers</li>
                  <li>Review committee skills and training needs</li>
                  <li>Encourage diverse committee membership</li>
                </ul>
              </div>
            </div>
          </div>
          
          <div class="col-12">
            <div class="card principle-card">
              <div class="card-body">
                <div class="d-flex align-items-start mb-3">
                  <div class="principle-icon bg-danger text-white me-3">5</div>
                  <div>
                    <h5>Behaving with Integrity</h5>
                    <span class="badge bg-danger">Ethics</span>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-6">
                    <ul class="mb-0">
                      <li>Act ethically and in the club's best interest</li>
                      <li>Manage conflicts of interest</li>
                      <li>Maintain safeguarding policies</li>
                    </ul>
                  </div>
                  <div class="col-md-6">
                    <ul class="mb-0">
                      <li>Ensure child protection compliance</li>
                      <li>Promote fair play and sportsmanship</li>
                      <li>Respect confidentiality</li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
      <?php elseif ($activeSection === 'roles'): ?>
        <div class="card mb-4">
          <div class="card-header bg-white">
            <h4 class="mb-0"><i class="bi bi-people me-2"></i>Committee Role Descriptions</h4>
          </div>
          <div class="card-body">
            <p>A well-functioning committee has clearly defined roles. Here are the typical positions for an angling club:</p>
          </div>
        </div>
        
        <div class="row g-3">
          <div class="col-md-6">
            <div class="card role-card h-100" style="border-left-color: #0d6efd;">
              <div class="card-body">
                <h5><i class="bi bi-person-badge text-primary me-2"></i>Chairperson</h5>
                <p class="text-muted small">Leads the club and chairs meetings</p>
                <ul class="small mb-0">
                  <li>Chairs committee and general meetings</li>
                  <li>Provides leadership and direction</li>
                  <li>Acts as spokesperson for the club</li>
                  <li>Ensures committee functions effectively</li>
                  <li>Mediates disputes and maintains order</li>
                </ul>
              </div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="card role-card h-100" style="border-left-color: #198754;">
              <div class="card-body">
                <h5><i class="bi bi-pencil-square text-success me-2"></i>Secretary</h5>
                <p class="text-muted small">Manages administration and communications</p>
                <ul class="small mb-0">
                  <li>Organises meetings and prepares agendas</li>
                  <li>Records and distributes minutes</li>
                  <li>Handles correspondence</li>
                  <li>Maintains club records and documents</li>
                  <li>Ensures compliance with constitution</li>
                </ul>
              </div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="card role-card h-100" style="border-left-color: #ffc107;">
              <div class="card-body">
                <h5><i class="bi bi-cash-coin text-warning me-2"></i>Treasurer</h5>
                <p class="text-muted small">Manages club finances</p>
                <ul class="small mb-0">
                  <li>Maintains accurate financial records</li>
                  <li>Manages bank accounts and payments</li>
                  <li>Prepares financial reports</li>
                  <li>Presents accounts at AGM</li>
                  <li>Advises on financial matters</li>
                </ul>
              </div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="card role-card h-100" style="border-left-color: #17a2b8;">
              <div class="card-body">
                <h5><i class="bi bi-megaphone text-info me-2"></i>PRO (Public Relations)</h5>
                <p class="text-muted small">Promotes the club publicly</p>
                <ul class="small mb-0">
                  <li>Manages social media and website</li>
                  <li>Writes press releases and news</li>
                  <li>Promotes events and competitions</li>
                  <li>Documents club activities (photos/reports)</li>
                  <li>Builds relationships with media</li>
                </ul>
              </div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="card role-card h-100" style="border-left-color: #dc3545;">
              <div class="card-body">
                <h5><i class="bi bi-shield-check text-danger me-2"></i>Children's Welfare Officer</h5>
                <p class="text-muted small">Leads safeguarding for junior members</p>
                <ul class="small mb-0">
                  <li>Ensures safeguarding policies are followed</li>
                  <li>Acts as first point of contact for concerns</li>
                  <li>Maintains vetting records</li>
                  <li>Attends safeguarding training</li>
                  <li>Reports concerns to appropriate authorities</li>
                </ul>
              </div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="card role-card h-100" style="border-left-color: #6f42c1;">
              <div class="card-body">
                <h5><i class="bi bi-trophy text-purple me-2"></i>Competition Secretary</h5>
                <p class="text-muted small">Organises club competitions</p>
                <ul class="small mb-0">
                  <li>Plans and schedules competitions</li>
                  <li>Manages entries and draws</li>
                  <li>Records results and maintains leaderboards</li>
                  <li>Coordinates with venues</li>
                  <li>Liaises with federation for inter-club events</li>
                </ul>
              </div>
            </div>
          </div>
        </div>
        
      <?php elseif ($activeSection === 'meetings'): ?>
        <div class="card mb-4">
          <div class="card-header bg-white">
            <h4 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Meetings & AGM Guide</h4>
          </div>
          <div class="card-body">
            <p>Well-run meetings are essential for good governance. Here's guidance on running effective committee meetings and your Annual General Meeting.</p>
          </div>
        </div>
        
        <div class="row g-4">
          <div class="col-md-6">
            <div class="card h-100">
              <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Committee Meetings</h5>
              </div>
              <div class="card-body">
                <h6>Before the Meeting</h6>
                <div class="checklist-item"><i class="bi bi-check-circle text-success me-2"></i>Set date, time, and venue (or online link)</div>
                <div class="checklist-item"><i class="bi bi-check-circle text-success me-2"></i>Circulate agenda at least 3 days in advance</div>
                <div class="checklist-item"><i class="bi bi-check-circle text-success me-2"></i>Attach previous minutes and relevant reports</div>
                
                <h6 class="mt-3">During the Meeting</h6>
                <div class="checklist-item"><i class="bi bi-check-circle text-success me-2"></i>Record attendance and apologies</div>
                <div class="checklist-item"><i class="bi bi-check-circle text-success me-2"></i>Approve previous minutes</div>
                <div class="checklist-item"><i class="bi bi-check-circle text-success me-2"></i>Follow the agenda</div>
                <div class="checklist-item"><i class="bi bi-check-circle text-success me-2"></i>Record decisions and action items</div>
                <div class="checklist-item"><i class="bi bi-check-circle text-success me-2"></i>Note who is responsible for each action</div>
                
                <h6 class="mt-3">After the Meeting</h6>
                <div class="checklist-item"><i class="bi bi-check-circle text-success me-2"></i>Circulate draft minutes within 7 days</div>
                <div class="checklist-item"><i class="bi bi-check-circle text-success me-2"></i>Follow up on action items</div>
                <div class="checklist-item"><i class="bi bi-check-circle text-success me-2"></i>Store minutes securely</div>
              </div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="card h-100">
              <div class="card-header bg-success text-white">
                <h5 class="mb-0">Annual General Meeting (AGM)</h5>
              </div>
              <div class="card-body">
                <h6>AGM Agenda Template</h6>
                <ol class="small">
                  <li>Welcome and opening by Chairperson</li>
                  <li>Apologies for absence</li>
                  <li>Minutes of previous AGM</li>
                  <li>Matters arising from minutes</li>
                  <li>Chairperson's report</li>
                  <li>Secretary's report</li>
                  <li>Treasurer's report and accounts</li>
                  <li>Adoption of accounts</li>
                  <li>Election of officers</li>
                  <li>Motions submitted</li>
                  <li>Setting of membership fees</li>
                  <li>Any other business (AOB)</li>
                  <li>Close of meeting</li>
                </ol>
                
                <div class="alert alert-info small mb-0 mt-3">
                  <i class="bi bi-info-circle me-1"></i>
                  <strong>Tip:</strong> Your constitution should specify how much notice is required for the AGM (typically 14-28 days) and how motions should be submitted.
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="card mt-4">
          <div class="card-body">
            <h5>Quick Actions</h5>
            <div class="d-flex gap-2 flex-wrap">
              <a href="/public/admin/meetings.php?club_id=<?= $clubId ?>" class="btn btn-outline-primary">
                <i class="bi bi-plus me-1"></i>Schedule a Meeting
              </a>
              <a href="/public/admin/policies.php?club_id=<?= $clubId ?>&tab=constitution" class="btn btn-outline-secondary">
                <i class="bi bi-file-text me-1"></i>View Constitution
              </a>
            </div>
          </div>
        </div>
        
      <?php elseif ($activeSection === 'safeguarding'): ?>
        <div class="card mb-4">
          <div class="card-header bg-danger text-white">
            <h4 class="mb-0"><i class="bi bi-shield-check me-2"></i>Safeguarding & Child Protection</h4>
          </div>
          <div class="card-body">
            <div class="alert alert-danger">
              <i class="bi bi-exclamation-triangle me-2"></i>
              <strong>Legal Requirement:</strong> Under the National Vetting Bureau Acts 2012-2016, all persons with access to children or vulnerable adults must be vetted. Non-compliance is a criminal offence.
            </div>
            <p>Safeguarding is everyone's responsibility. Clubs must create a safe environment for all members, especially young people and vulnerable adults.</p>
          </div>
        </div>
        
        <div class="row g-4">
          <div class="col-md-6">
            <div class="card h-100">
              <div class="card-header bg-white">
                <h5 class="mb-0">Essential Policies</h5>
              </div>
              <div class="card-body">
                <div class="checklist-item">
                  <i class="bi bi-file-earmark-text text-primary me-2"></i>
                  <strong>Safeguarding Policy</strong>
                  <small class="d-block text-muted">Your club's approach to protecting children</small>
                </div>
                <div class="checklist-item">
                  <i class="bi bi-file-earmark-text text-primary me-2"></i>
                  <strong>Code of Conduct</strong>
                  <small class="d-block text-muted">Expected behaviours for adults and young people</small>
                </div>
                <div class="checklist-item">
                  <i class="bi bi-file-earmark-text text-primary me-2"></i>
                  <strong>Anti-Bullying Policy</strong>
                  <small class="d-block text-muted">How bullying is prevented and addressed</small>
                </div>
                <div class="checklist-item">
                  <i class="bi bi-file-earmark-text text-primary me-2"></i>
                  <strong>Photography Policy</strong>
                  <small class="d-block text-muted">Rules for photographing/filming young people</small>
                </div>
                <div class="checklist-item">
                  <i class="bi bi-file-earmark-text text-primary me-2"></i>
                  <strong>Communications Policy</strong>
                  <small class="d-block text-muted">Safe communication with young members</small>
                </div>
              </div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="card h-100">
              <div class="card-header bg-white">
                <h5 class="mb-0">Key Requirements</h5>
              </div>
              <div class="card-body">
                <div class="checklist-item">
                  <i class="bi bi-person-check text-success me-2"></i>
                  <strong>Appoint a Children's Welfare Officer</strong>
                  <small class="d-block text-muted">First point of contact for safeguarding</small>
                </div>
                <div class="checklist-item">
                  <i class="bi bi-card-checklist text-success me-2"></i>
                  <strong>Garda Vetting</strong>
                  <small class="d-block text-muted">All adults working with young people must be vetted</small>
                </div>
                <div class="checklist-item">
                  <i class="bi bi-mortarboard text-success me-2"></i>
                  <strong>Safeguarding Training</strong>
                  <small class="d-block text-muted">Relevant training for committee and coaches</small>
                </div>
                <div class="checklist-item">
                  <i class="bi bi-file-medical text-success me-2"></i>
                  <strong>Parental Consent Forms</strong>
                  <small class="d-block text-muted">Written consent for junior participation</small>
                </div>
                <div class="checklist-item">
                  <i class="bi bi-telephone text-success me-2"></i>
                  <strong>Reporting Procedures</strong>
                  <small class="d-block text-muted">Clear process for reporting concerns to TUSLA</small>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="card mt-4">
          <div class="card-header bg-white">
            <h5 class="mb-0">Reporting a Concern</h5>
          </div>
          <div class="card-body">
            <p>If you have a concern about a child's safety or welfare:</p>
            <ol>
              <li>Report immediately to the club's Children's Welfare Officer</li>
              <li>If the child is in immediate danger, contact the Gardai</li>
              <li>For child protection concerns, contact TUSLA</li>
            </ol>
            <div class="row g-3 mt-2">
              <div class="col-md-4">
                <div class="p-3 bg-light rounded text-center">
                  <strong>TUSLA</strong><br>
                  <a href="https://www.tusla.ie" target="_blank">www.tusla.ie</a>
                </div>
              </div>
              <div class="col-md-4">
                <div class="p-3 bg-light rounded text-center">
                  <strong>Gardai Emergency</strong><br>
                  <span class="h5">999 / 112</span>
                </div>
              </div>
              <div class="col-md-4">
                <div class="p-3 bg-light rounded text-center">
                  <strong>ISPCC Childline</strong><br>
                  <span class="h5">1800 66 66 66</span>
                </div>
              </div>
            </div>
          </div>
        </div>
        
      <?php elseif ($activeSection === 'finance'): ?>
        <div class="card mb-4">
          <div class="card-header bg-warning">
            <h4 class="mb-0"><i class="bi bi-cash-stack me-2"></i>Financial Controls & Best Practice</h4>
          </div>
          <div class="card-body">
            <p>Sound financial management protects the club and ensures member trust. Here's guidance on financial controls.</p>
          </div>
        </div>
        
        <div class="row g-4">
          <div class="col-md-6">
            <div class="card h-100">
              <div class="card-header bg-white">
                <h5 class="mb-0">Essential Controls</h5>
              </div>
              <div class="card-body">
                <div class="checklist-item">
                  <i class="bi bi-check2-square text-success me-2"></i>
                  <strong>Dual Signatures</strong>
                  <small class="d-block text-muted">Require two signatories for payments over a set amount</small>
                </div>
                <div class="checklist-item">
                  <i class="bi bi-check2-square text-success me-2"></i>
                  <strong>Receipt Keeping</strong>
                  <small class="d-block text-muted">Retain receipts for all expenditure</small>
                </div>
                <div class="checklist-item">
                  <i class="bi bi-check2-square text-success me-2"></i>
                  <strong>Regular Reconciliation</strong>
                  <small class="d-block text-muted">Match bank statements with records monthly</small>
                </div>
                <div class="checklist-item">
                  <i class="bi bi-check2-square text-success me-2"></i>
                  <strong>Approval Process</strong>
                  <small class="d-block text-muted">Committee approval for significant spending</small>
                </div>
                <div class="checklist-item">
                  <i class="bi bi-check2-square text-success me-2"></i>
                  <strong>Segregation of Duties</strong>
                  <small class="d-block text-muted">Different people handle money and records</small>
                </div>
              </div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="card h-100">
              <div class="card-header bg-white">
                <h5 class="mb-0">Annual Requirements</h5>
              </div>
              <div class="card-body">
                <div class="checklist-item">
                  <i class="bi bi-calendar-check text-primary me-2"></i>
                  <strong>Annual Accounts</strong>
                  <small class="d-block text-muted">Prepare income & expenditure statement</small>
                </div>
                <div class="checklist-item">
                  <i class="bi bi-calendar-check text-primary me-2"></i>
                  <strong>Balance Sheet</strong>
                  <small class="d-block text-muted">Statement of assets and liabilities</small>
                </div>
                <div class="checklist-item">
                  <i class="bi bi-calendar-check text-primary me-2"></i>
                  <strong>Independent Review</strong>
                  <small class="d-block text-muted">Accounts reviewed by non-committee member</small>
                </div>
                <div class="checklist-item">
                  <i class="bi bi-calendar-check text-primary me-2"></i>
                  <strong>AGM Presentation</strong>
                  <small class="d-block text-muted">Present accounts for member adoption</small>
                </div>
                <div class="checklist-item">
                  <i class="bi bi-calendar-check text-primary me-2"></i>
                  <strong>Budget Planning</strong>
                  <small class="d-block text-muted">Prepare budget for coming year</small>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="card mt-4">
          <div class="card-body">
            <h5>Quick Actions</h5>
            <div class="d-flex gap-2 flex-wrap">
              <a href="/public/admin/finances.php?club_id=<?= $clubId ?>" class="btn btn-outline-warning">
                <i class="bi bi-cash-stack me-1"></i>View Transactions
              </a>
              <a href="/public/admin/finances.php?club_id=<?= $clubId ?>&report=1" class="btn btn-outline-secondary">
                <i class="bi bi-graph-up me-1"></i>Financial Reports
              </a>
            </div>
          </div>
        </div>
        
      <?php elseif ($activeSection === 'resources'): ?>
        <div class="card mb-4">
          <div class="card-header bg-white">
            <h4 class="mb-0"><i class="bi bi-link-45deg me-2"></i>External Resources</h4>
          </div>
          <div class="card-body">
            <p>Official resources from Sport Ireland, NCFFI, and other bodies to support your club's governance.</p>
          </div>
        </div>
        
        <div class="row g-4">
          <div class="col-md-6">
            <div class="card h-100">
              <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Sport Ireland</h5>
              </div>
              <div class="card-body">
                <a href="https://www.sportireland.ie/GovernanceCode" target="_blank" class="resource-link border-bottom">
                  <i class="bi bi-box-arrow-up-right me-2"></i>Governance Code for Sport
                  <small class="d-block text-muted">The official governance code guide</small>
                </a>
                <a href="https://www.sportireland.ie/GovernanceCode/Resources" target="_blank" class="resource-link border-bottom">
                  <i class="bi bi-box-arrow-up-right me-2"></i>Governance Resources
                  <small class="d-block text-muted">Templates, guides, and support materials</small>
                </a>
                <a href="https://www.sportireland.ie/governance-code/implementing-the-code" target="_blank" class="resource-link">
                  <i class="bi bi-box-arrow-up-right me-2"></i>Implementing the Code
                  <small class="d-block text-muted">Step-by-step implementation guide</small>
                </a>
              </div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="card h-100">
              <div class="card-header bg-success text-white">
                <h5 class="mb-0">NCFFI</h5>
              </div>
              <div class="card-body">
                <a href="https://www.ncffi.ie/about-us/governance-code/" target="_blank" class="resource-link border-bottom">
                  <i class="bi bi-box-arrow-up-right me-2"></i>NCFFI Governance
                  <small class="d-block text-muted">Federation governance compliance</small>
                </a>
                <a href="https://www.ncffi.ie/safeguarding/" target="_blank" class="resource-link border-bottom">
                  <i class="bi bi-box-arrow-up-right me-2"></i>Safeguarding
                  <small class="d-block text-muted">Child protection policies and guides</small>
                </a>
                <a href="https://www.ncffi.ie/club-documentation/" target="_blank" class="resource-link">
                  <i class="bi bi-box-arrow-up-right me-2"></i>Club Documentation
                  <small class="d-block text-muted">Templates and forms for clubs</small>
                </a>
              </div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="card h-100">
              <div class="card-header bg-danger text-white">
                <h5 class="mb-0">Child Protection</h5>
              </div>
              <div class="card-body">
                <a href="https://www.tusla.ie/children-first/" target="_blank" class="resource-link border-bottom">
                  <i class="bi bi-box-arrow-up-right me-2"></i>TUSLA Children First
                  <small class="d-block text-muted">National guidance for child protection</small>
                </a>
                <a href="https://vetting.garda.ie/" target="_blank" class="resource-link">
                  <i class="bi bi-box-arrow-up-right me-2"></i>Garda Vetting
                  <small class="d-block text-muted">National Vetting Bureau information</small>
                </a>
              </div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="card h-100">
              <div class="card-header bg-info text-white">
                <h5 class="mb-0">Other Federations</h5>
              </div>
              <div class="card-body">
                <a href="https://www.anglingcouncil.ie/" target="_blank" class="resource-link border-bottom">
                  <i class="bi bi-box-arrow-up-right me-2"></i>Angling Council of Ireland
                  <small class="d-block text-muted">National governing body for angling</small>
                </a>
                <a href="https://www.fisheriesireland.ie/" target="_blank" class="resource-link border-bottom">
                  <i class="bi bi-box-arrow-up-right me-2"></i>Inland Fisheries Ireland
                  <small class="d-block text-muted">Regulatory body for inland fisheries</small>
                </a>
                <a href="https://specimenfish.ie/" target="_blank" class="resource-link">
                  <i class="bi bi-box-arrow-up-right me-2"></i>Irish Specimen Fish Committee
                  <small class="d-block text-muted">Specimen fish verification</small>
                </a>
              </div>
            </div>
          </div>
        </div>
        
      <?php endif; ?>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
