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
$committeeRole = $memberRow ? ($memberRow['committee_role'] ?? null) : null;

$canViewCommittee = $adminRow || in_array($committeeRole, ['chairperson', 'secretary', 'treasurer', 'pro', 'cwo', 'competition_secretary', 'committee']);

if (!$canViewCommittee) {
  http_response_code(403);
  exit('Only committee members can access this section');
}

$activeTab = $_GET['tab'] ?? 'structure';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Committee Guide - <?= e($club['name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    .role-card { transition: transform 0.2s, box-shadow 0.2s; border-left: 4px solid; }
    .role-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .duty-item { padding: 0.5rem 0; border-bottom: 1px solid #eee; }
    .duty-item:last-child { border-bottom: none; }
    .timeline-item { position: relative; padding-left: 2rem; padding-bottom: 1.5rem; border-left: 2px solid #dee2e6; }
    .timeline-item:last-child { border-left-color: transparent; }
    .timeline-dot { position: absolute; left: -8px; top: 0; width: 14px; height: 14px; border-radius: 50%; background: #198754; border: 2px solid white; }
    .best-practice { background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 12px; padding: 1.5rem; margin-bottom: 1rem; }
    .org-chart { display: flex; flex-direction: column; align-items: center; gap: 1rem; }
    .org-level { display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center; }
    .org-box { background: white; border: 2px solid #dee2e6; border-radius: 8px; padding: 1rem 1.5rem; text-align: center; min-width: 140px; }
    .org-box.primary { border-color: #0d6efd; background: #e7f1ff; }
    .org-box.success { border-color: #198754; background: #d1e7dd; }
    .org-connector { width: 2px; height: 20px; background: #dee2e6; }
  </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="/">Angling Ireland</a>
    <div class="ms-auto">
      <a class="btn btn-outline-light btn-sm" href="/public/dashboard.php">Dashboard</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2><i class="bi bi-people me-2"></i>Committee Guide</h2>
      <p class="text-muted mb-0"><?= e($club['name']) ?> - Roles, Duties & Best Practices</p>
    </div>
    <a href="/public/club.php?slug=<?= e($club['slug']) ?>" class="btn btn-outline-secondary">Back to Club</a>
  </div>
  
  <ul class="nav nav-tabs mb-4">
    <li class="nav-item">
      <a class="nav-link <?= $activeTab === 'structure' ? 'active' : '' ?>" href="?club_id=<?= $clubId ?>&tab=structure">
        <i class="bi bi-diagram-3 me-1"></i>Structure
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $activeTab === 'roles' ? 'active' : '' ?>" href="?club_id=<?= $clubId ?>&tab=roles">
        <i class="bi bi-person-badge me-1"></i>Roles & Duties
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $activeTab === 'meetings' ? 'active' : '' ?>" href="?club_id=<?= $clubId ?>&tab=meetings">
        <i class="bi bi-calendar-check me-1"></i>Running Meetings
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $activeTab === 'bestpractice' ? 'active' : '' ?>" href="?club_id=<?= $clubId ?>&tab=bestpractice">
        <i class="bi bi-star me-1"></i>Best Practices
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $activeTab === 'calendar' ? 'active' : '' ?>" href="?club_id=<?= $clubId ?>&tab=calendar">
        <i class="bi bi-calendar3 me-1"></i>Annual Calendar
      </a>
    </li>
  </ul>
  
  <?php if ($activeTab === 'structure'): ?>
    <div class="card mb-4">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Typical Committee Structure</h5>
      </div>
      <div class="card-body">
        <p class="text-muted">A well-structured committee ensures clear lines of responsibility and effective decision-making.</p>
        
        <div class="org-chart my-4">
          <div class="org-level">
            <div class="org-box primary">
              <i class="bi bi-person-badge d-block mb-1" style="font-size: 1.5rem;"></i>
              <strong>Chairperson</strong>
            </div>
          </div>
          <div class="org-connector"></div>
          <div class="org-level">
            <div class="org-box success">
              <i class="bi bi-pencil-square d-block mb-1"></i>
              <strong>Secretary</strong>
            </div>
            <div class="org-box success">
              <i class="bi bi-cash-coin d-block mb-1"></i>
              <strong>Treasurer</strong>
            </div>
          </div>
          <div class="org-connector"></div>
          <div class="org-level">
            <div class="org-box">
              <i class="bi bi-megaphone d-block mb-1"></i>
              <strong>PRO</strong>
            </div>
            <div class="org-box">
              <i class="bi bi-shield-check d-block mb-1"></i>
              <strong>CWO</strong>
            </div>
            <div class="org-box">
              <i class="bi bi-trophy d-block mb-1"></i>
              <strong>Comp. Sec.</strong>
            </div>
          </div>
          <div class="org-connector"></div>
          <div class="org-level">
            <div class="org-box">
              <i class="bi bi-people d-block mb-1"></i>
              <strong>Ordinary Committee Members</strong>
              <small class="d-block text-muted">(2-4 members)</small>
            </div>
          </div>
        </div>
        
        <div class="row g-4 mt-3">
          <div class="col-md-6">
            <div class="card h-100 border-primary">
              <div class="card-header bg-primary text-white">
                <strong>Officers (Required)</strong>
              </div>
              <div class="card-body">
                <ul class="mb-0">
                  <li><strong>Chairperson</strong> - Leads the club</li>
                  <li><strong>Secretary</strong> - Administration</li>
                  <li><strong>Treasurer</strong> - Finances</li>
                </ul>
                <p class="text-muted small mt-3 mb-0">These three roles are the minimum required for any club.</p>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card h-100 border-success">
              <div class="card-header bg-success text-white">
                <strong>Additional Officers (Recommended)</strong>
              </div>
              <div class="card-body">
                <ul class="mb-0">
                  <li><strong>Vice-Chairperson</strong> - Supports Chair</li>
                  <li><strong>PRO</strong> - Public Relations</li>
                  <li><strong>CWO</strong> - Children's Welfare</li>
                  <li><strong>Competition Secretary</strong> - Events</li>
                  <li><strong>Fishery Officer</strong> - Waters management</li>
                </ul>
              </div>
            </div>
          </div>
        </div>
        
        <div class="alert alert-info mt-4 mb-0">
          <i class="bi bi-lightbulb me-2"></i>
          <strong>Tip:</strong> Clubs with junior members <strong>must</strong> have a designated Children's Welfare Officer (CWO) - this is a legal requirement.
        </div>
      </div>
    </div>
    
    <div class="card">
      <div class="card-header bg-white">
        <h5 class="mb-0">Committee Size Guidelines</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered mb-0">
            <thead class="table-light">
              <tr>
                <th>Club Size</th>
                <th>Recommended Committee Size</th>
                <th>Notes</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><strong>Small Club</strong><br><small class="text-muted">Under 30 members</small></td>
                <td>5-6 members</td>
                <td>Chair, Secretary, Treasurer + 2-3 ordinary members</td>
              </tr>
              <tr>
                <td><strong>Medium Club</strong><br><small class="text-muted">30-100 members</small></td>
                <td>7-9 members</td>
                <td>Add PRO, CWO, Competition Secretary</td>
              </tr>
              <tr>
                <td><strong>Large Club</strong><br><small class="text-muted">Over 100 members</small></td>
                <td>9-12 members</td>
                <td>May include sub-committees for specific tasks</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p class="text-muted small mt-3 mb-0">An odd number is preferred for voting purposes. Too large a committee can slow decision-making.</p>
      </div>
    </div>
    
  <?php elseif ($activeTab === 'roles'): ?>
    <div class="row g-4">
      <div class="col-lg-6">
        <div class="card role-card h-100" style="border-left-color: #0d6efd;">
          <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-person-badge text-primary me-2"></i>Chairperson</h5>
          </div>
          <div class="card-body">
            <p class="text-muted">The leader of the club who provides direction and chairs meetings.</p>
            <h6>Key Duties:</h6>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Chair all committee and general meetings</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Provide leadership and strategic direction</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Act as official spokesperson for the club</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Ensure the committee functions effectively</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Mediate disputes and maintain order</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Represent the club at external events</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Have a casting vote in tied decisions</div>
            <h6 class="mt-3">Time Commitment:</h6>
            <p class="small text-muted mb-0">3-5 hours per week during busy periods, less at other times.</p>
          </div>
        </div>
      </div>
      
      <div class="col-lg-6">
        <div class="card role-card h-100" style="border-left-color: #198754;">
          <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-pencil-square text-success me-2"></i>Secretary</h5>
          </div>
          <div class="card-body">
            <p class="text-muted">The administrative backbone of the club.</p>
            <h6>Key Duties:</h6>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Organise meetings and prepare agendas</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Record and distribute meeting minutes</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Handle all club correspondence</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Maintain membership records</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Keep club documents organised and secure</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Ensure constitutional compliance</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Coordinate with national bodies (affiliation)</div>
            <h6 class="mt-3">Time Commitment:</h6>
            <p class="small text-muted mb-0">4-6 hours per week - often the busiest role on the committee.</p>
          </div>
        </div>
      </div>
      
      <div class="col-lg-6">
        <div class="card role-card h-100" style="border-left-color: #ffc107;">
          <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-cash-coin text-warning me-2"></i>Treasurer</h5>
          </div>
          <div class="card-body">
            <p class="text-muted">Responsible for all financial matters.</p>
            <h6>Key Duties:</h6>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Maintain accurate financial records</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Manage bank accounts and payments</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Collect membership fees and other income</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Pay bills and expenses promptly</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Prepare financial reports for meetings</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Present accounts at the AGM</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Advise the committee on financial matters</div>
            <h6 class="mt-3">Time Commitment:</h6>
            <p class="small text-muted mb-0">2-4 hours per week, more around AGM and renewal periods.</p>
          </div>
        </div>
      </div>
      
      <div class="col-lg-6">
        <div class="card role-card h-100" style="border-left-color: #17a2b8;">
          <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-megaphone text-info me-2"></i>Public Relations Officer (PRO)</h5>
          </div>
          <div class="card-body">
            <p class="text-muted">Promotes the club and manages communications.</p>
            <h6>Key Duties:</h6>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Manage social media accounts</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Update the club website</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Write press releases and news articles</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Promote events and competitions</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Document club activities (photos, reports)</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Build relationships with local media</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Communicate with members (newsletters)</div>
            <h6 class="mt-3">Time Commitment:</h6>
            <p class="small text-muted mb-0">2-4 hours per week, varies with events and activities.</p>
          </div>
        </div>
      </div>
      
      <div class="col-lg-6">
        <div class="card role-card h-100" style="border-left-color: #dc3545;">
          <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-shield-check text-danger me-2"></i>Children's Welfare Officer (CWO)</h5>
          </div>
          <div class="card-body">
            <p class="text-muted">Leads child safeguarding for the club. <strong>Mandatory if you have junior members.</strong></p>
            <h6>Key Duties:</h6>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Ensure safeguarding policies are followed</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Act as first point of contact for concerns</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Maintain Garda vetting records</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Complete safeguarding training</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Report concerns to appropriate authorities</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Promote child-friendly practices</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Keep up to date with legislation</div>
            <h6 class="mt-3">Training Required:</h6>
            <p class="small text-muted mb-0">Sport Ireland Safeguarding courses (Safeguarding 1, 2 & 3)</p>
          </div>
        </div>
      </div>
      
      <div class="col-lg-6">
        <div class="card role-card h-100" style="border-left-color: #6f42c1;">
          <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-trophy text-purple me-2"></i>Competition Secretary</h5>
          </div>
          <div class="card-body">
            <p class="text-muted">Organises all competitive angling events.</p>
            <h6>Key Duties:</h6>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Plan and schedule competitions</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Manage entries and draws</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Record results and update leaderboards</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Coordinate with venues</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Liaise with federation for inter-club events</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Organise prizes and trophies</div>
            <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Ensure competition rules are followed</div>
            <h6 class="mt-3">Time Commitment:</h6>
            <p class="small text-muted mb-0">Variable - high during competition season, quiet off-season.</p>
          </div>
        </div>
      </div>
      
      <div class="col-12">
        <div class="card role-card" style="border-left-color: #6c757d;">
          <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-people text-secondary me-2"></i>Ordinary Committee Members</h5>
          </div>
          <div class="card-body">
            <p class="text-muted">Support the officers and contribute to club decisions.</p>
            <div class="row">
              <div class="col-md-6">
                <h6>Key Duties:</h6>
                <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Attend committee meetings regularly</div>
                <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Contribute to discussions and decisions</div>
                <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Take on specific tasks as assigned</div>
                <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Support officers when needed</div>
              </div>
              <div class="col-md-6">
                <h6>May Also:</h6>
                <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Lead sub-committees or working groups</div>
                <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Help organise events</div>
                <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Bring member feedback to committee</div>
                <div class="duty-item"><i class="bi bi-check2 text-success me-2"></i>Prepare for future officer roles</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
  <?php elseif ($activeTab === 'meetings'): ?>
    <div class="row g-4">
      <div class="col-md-6">
        <div class="card h-100">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Before the Meeting</h5>
          </div>
          <div class="card-body">
            <div class="timeline-item">
              <div class="timeline-dot"></div>
              <strong>1 Week Before</strong>
              <ul class="small mt-1 mb-0">
                <li>Set date, time, and venue</li>
                <li>Secretary drafts agenda</li>
                <li>Chair reviews and approves agenda</li>
              </ul>
            </div>
            <div class="timeline-item">
              <div class="timeline-dot"></div>
              <strong>3-5 Days Before</strong>
              <ul class="small mt-1 mb-0">
                <li>Circulate agenda to all members</li>
                <li>Attach previous minutes</li>
                <li>Include any reports or proposals</li>
              </ul>
            </div>
            <div class="timeline-item">
              <div class="timeline-dot"></div>
              <strong>Day Before</strong>
              <ul class="small mt-1 mb-0">
                <li>Confirm attendance</li>
                <li>Prepare meeting room/online link</li>
                <li>Print necessary documents</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="card h-100">
          <div class="card-header bg-success text-white">
            <h5 class="mb-0">Running the Meeting</h5>
          </div>
          <div class="card-body">
            <h6>Standard Agenda Order</h6>
            <ol class="small">
              <li><strong>Opening</strong> - Chair welcomes, confirms quorum</li>
              <li><strong>Apologies</strong> - Record who is absent</li>
              <li><strong>Minutes</strong> - Approve previous minutes</li>
              <li><strong>Matters Arising</strong> - Updates on previous actions</li>
              <li><strong>Correspondence</strong> - Letters/emails received</li>
              <li><strong>Reports</strong> - Treasurer, Secretary, other officers</li>
              <li><strong>Main Business</strong> - Items on the agenda</li>
              <li><strong>Any Other Business</strong> - Brief items only</li>
              <li><strong>Date of Next Meeting</strong></li>
              <li><strong>Close</strong></li>
            </ol>
            
            <div class="alert alert-info small mb-0">
              <i class="bi bi-clock me-1"></i>
              <strong>Tip:</strong> Aim for 60-90 minutes maximum. Longer meetings lose focus and energy.
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-12">
        <div class="card">
          <div class="card-header bg-warning">
            <h5 class="mb-0">Chairperson's Meeting Tips</h5>
          </div>
          <div class="card-body">
            <div class="row g-4">
              <div class="col-md-4">
                <h6><i class="bi bi-clock text-primary me-2"></i>Time Management</h6>
                <ul class="small">
                  <li>Start and end on time</li>
                  <li>Allocate time to each item</li>
                  <li>Park lengthy discussions for later</li>
                  <li>Use a "car park" for off-topic issues</li>
                </ul>
              </div>
              <div class="col-md-4">
                <h6><i class="bi bi-people text-success me-2"></i>Participation</h6>
                <ul class="small">
                  <li>Encourage quieter members to speak</li>
                  <li>Prevent anyone dominating</li>
                  <li>Summarise key points</li>
                  <li>Ensure everyone understands decisions</li>
                </ul>
              </div>
              <div class="col-md-4">
                <h6><i class="bi bi-check-circle text-warning me-2"></i>Decision Making</h6>
                <ul class="small">
                  <li>State motions clearly</li>
                  <li>Call for a proposer and seconder</li>
                  <li>Take a vote when needed</li>
                  <li>Record the outcome clearly</li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="card h-100">
          <div class="card-header bg-info text-white">
            <h5 class="mb-0">After the Meeting</h5>
          </div>
          <div class="card-body">
            <div class="duty-item"><i class="bi bi-1-circle text-primary me-2"></i>Secretary drafts minutes within 48 hours</div>
            <div class="duty-item"><i class="bi bi-2-circle text-primary me-2"></i>Chair reviews draft minutes</div>
            <div class="duty-item"><i class="bi bi-3-circle text-primary me-2"></i>Circulate to committee within 7 days</div>
            <div class="duty-item"><i class="bi bi-4-circle text-primary me-2"></i>Action items assigned and tracked</div>
            <div class="duty-item"><i class="bi bi-5-circle text-primary me-2"></i>Store minutes securely</div>
          </div>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="card h-100">
          <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">Good Minutes Include</h5>
          </div>
          <div class="card-body">
            <div class="duty-item"><i class="bi bi-check text-success me-2"></i>Date, time, location, and attendees</div>
            <div class="duty-item"><i class="bi bi-check text-success me-2"></i>Key points discussed (not verbatim)</div>
            <div class="duty-item"><i class="bi bi-check text-success me-2"></i>Decisions made (with vote counts if applicable)</div>
            <div class="duty-item"><i class="bi bi-check text-success me-2"></i>Actions assigned (who, what, when)</div>
            <div class="duty-item"><i class="bi bi-check text-success me-2"></i>Date of next meeting</div>
            <div class="alert alert-warning small mt-3 mb-0">
              <i class="bi bi-exclamation-triangle me-1"></i>
              Minutes are a <strong>record of decisions</strong>, not a transcript of everything said.
            </div>
          </div>
        </div>
      </div>
    </div>
    
  <?php elseif ($activeTab === 'bestpractice'): ?>
    <div class="card mb-4">
      <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="bi bi-star me-2"></i>Best Practices for Running a Committee</h5>
      </div>
      <div class="card-body">
        <p>These guidelines will help your committee function effectively and maintain member trust.</p>
      </div>
    </div>
    
    <div class="row g-4">
      <div class="col-md-6">
        <div class="best-practice">
          <h5><i class="bi bi-people text-primary me-2"></i>1. Share the Load</h5>
          <ul class="mb-0">
            <li>Don't let one person do everything</li>
            <li>Delegate tasks to committee members</li>
            <li>Use working groups for big projects</li>
            <li>Rotate responsibilities where possible</li>
            <li>Recognise and thank volunteers</li>
          </ul>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="best-practice">
          <h5><i class="bi bi-chat-left-text text-success me-2"></i>2. Communicate Well</h5>
          <ul class="mb-0">
            <li>Keep members informed of decisions</li>
            <li>Use multiple channels (email, social, noticeboard)</li>
            <li>Be transparent about club finances</li>
            <li>Respond to member queries promptly</li>
            <li>Hold regular member meetings (not just AGM)</li>
          </ul>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="best-practice">
          <h5><i class="bi bi-calendar-check text-warning me-2"></i>3. Plan Ahead</h5>
          <ul class="mb-0">
            <li>Create an annual calendar of events</li>
            <li>Set goals at the start of each year</li>
            <li>Review progress regularly</li>
            <li>Plan for succession - who's next?</li>
            <li>Budget for the year ahead</li>
          </ul>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="best-practice">
          <h5><i class="bi bi-book text-info me-2"></i>4. Keep Good Records</h5>
          <ul class="mb-0">
            <li>Document all decisions in minutes</li>
            <li>Maintain up-to-date member records</li>
            <li>Keep financial records for 7 years</li>
            <li>Store important documents securely</li>
            <li>Create handover notes for new officers</li>
          </ul>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="best-practice">
          <h5><i class="bi bi-hand-thumbs-up text-danger me-2"></i>5. Stay Positive</h5>
          <ul class="mb-0">
            <li>Focus on what you can do, not obstacles</li>
            <li>Celebrate successes and milestones</li>
            <li>Welcome new ideas from members</li>
            <li>Address conflicts early and fairly</li>
            <li>Remember why you volunteer</li>
          </ul>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="best-practice">
          <h5><i class="bi bi-shield-check text-secondary me-2"></i>6. Act with Integrity</h5>
          <ul class="mb-0">
            <li>Declare conflicts of interest</li>
            <li>Make decisions in the club's interest</li>
            <li>Maintain confidentiality when appropriate</li>
            <li>Follow your constitution and rules</li>
            <li>Treat all members fairly</li>
          </ul>
        </div>
      </div>
    </div>
    
    <div class="card mt-4">
      <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-exclamation-triangle text-warning me-2"></i>Common Pitfalls to Avoid</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <ul>
              <li><strong>Over-reliance on one person</strong> - succession becomes impossible</li>
              <li><strong>Poor communication</strong> - members feel excluded</li>
              <li><strong>Not following your constitution</strong> - decisions can be challenged</li>
              <li><strong>Ignoring conflicts</strong> - small issues become big problems</li>
            </ul>
          </div>
          <div class="col-md-6">
            <ul>
              <li><strong>No succession planning</strong> - scramble to fill roles at AGM</li>
              <li><strong>Unclear decision-making</strong> - members don't know who decided what</li>
              <li><strong>Financial opacity</strong> - breeds mistrust</li>
              <li><strong>Burnout</strong> - recognise when volunteers need a break</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
    
  <?php elseif ($activeTab === 'calendar'): ?>
    <div class="card mb-4">
      <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="bi bi-calendar3 me-2"></i>Annual Committee Calendar</h5>
      </div>
      <div class="card-body">
        <p>A sample annual calendar to help your committee plan ahead. Adjust dates to suit your club's needs.</p>
      </div>
    </div>
    
    <div class="row g-3">
      <div class="col-md-6 col-lg-4">
        <div class="card h-100">
          <div class="card-header bg-primary text-white">
            <strong>January - February</strong>
          </div>
          <div class="card-body small">
            <ul class="mb-0">
              <li>Prepare for AGM</li>
              <li>Draft annual reports</li>
              <li>Finalise accounts for audit</li>
              <li>Plan competition calendar</li>
              <li>Membership renewal reminders</li>
            </ul>
          </div>
        </div>
      </div>
      
      <div class="col-md-6 col-lg-4">
        <div class="card h-100">
          <div class="card-header bg-success text-white">
            <strong>March - April</strong>
          </div>
          <div class="card-body small">
            <ul class="mb-0">
              <li><strong>AGM</strong> (typically March)</li>
              <li>New committee takes office</li>
              <li>Season opening preparations</li>
              <li>Submit federation affiliation</li>
              <li>Insurance renewal</li>
            </ul>
          </div>
        </div>
      </div>
      
      <div class="col-md-6 col-lg-4">
        <div class="card h-100">
          <div class="card-header bg-warning">
            <strong>May - June</strong>
          </div>
          <div class="card-body small">
            <ul class="mb-0">
              <li>Competition season begins</li>
              <li>Review membership numbers</li>
              <li>Plan summer events</li>
              <li>Recruit new members</li>
              <li>Committee meeting</li>
            </ul>
          </div>
        </div>
      </div>
      
      <div class="col-md-6 col-lg-4">
        <div class="card h-100">
          <div class="card-header bg-danger text-white">
            <strong>July - August</strong>
          </div>
          <div class="card-body small">
            <ul class="mb-0">
              <li>Peak competition season</li>
              <li>Junior/family events</li>
              <li>Social activities</li>
              <li>Mid-year financial review</li>
              <li>Committee meeting</li>
            </ul>
          </div>
        </div>
      </div>
      
      <div class="col-md-6 col-lg-4">
        <div class="card h-100">
          <div class="card-header bg-secondary text-white">
            <strong>September - October</strong>
          </div>
          <div class="card-body small">
            <ul class="mb-0">
              <li>Championship finals</li>
              <li>Annual presentation night</li>
              <li>Review season performance</li>
              <li>Autumn work parties</li>
              <li>Committee meeting</li>
            </ul>
          </div>
        </div>
      </div>
      
      <div class="col-md-6 col-lg-4">
        <div class="card h-100">
          <div class="card-header bg-dark text-white">
            <strong>November - December</strong>
          </div>
          <div class="card-body small">
            <ul class="mb-0">
              <li>Prepare for AGM</li>
              <li>Annual accounts preparation</li>
              <li>Set next year's calendar</li>
              <li>Holiday social event</li>
              <li>Committee meeting</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
    
    <div class="card mt-4">
      <div class="card-header bg-white">
        <h5 class="mb-0">Recurring Tasks</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-4">
            <h6>Monthly</h6>
            <ul class="small">
              <li>Committee meeting</li>
              <li>Bank reconciliation</li>
              <li>Social media updates</li>
            </ul>
          </div>
          <div class="col-md-4">
            <h6>Quarterly</h6>
            <ul class="small">
              <li>Financial review</li>
              <li>Membership check</li>
              <li>Policy review</li>
            </ul>
          </div>
          <div class="col-md-4">
            <h6>Annually</h6>
            <ul class="small">
              <li>AGM</li>
              <li>Insurance renewal</li>
              <li>Federation affiliation</li>
              <li>Garda vetting renewal</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
    
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
