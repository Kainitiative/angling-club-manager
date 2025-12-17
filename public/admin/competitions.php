<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

require_login();

$userId = current_user_id();
$clubId = (int)($_GET['club_id'] ?? 0);
$message = '';
$messageType = '';

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

if (!$adminRow) {
  http_response_code(403);
  exit('You are not an admin of this club');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  
  if ($action === 'create') {
    $title = trim($_POST['title'] ?? '');
    $venueName = trim($_POST['venue_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $competitionDate = trim($_POST['competition_date'] ?? '');
    $startTime = trim($_POST['start_time'] ?? '');
    $addressLine1 = trim($_POST['address_line1'] ?? '');
    $addressLine2 = trim($_POST['address_line2'] ?? '');
    $town = trim($_POST['town'] ?? '');
    $county = trim($_POST['county'] ?? '');
    $postcode = trim($_POST['postcode'] ?? '');
    $country = trim($_POST['country'] ?? 'United Kingdom');
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');
    $visibility = $_POST['visibility'] ?? 'open';
    
    $errors = [];
    if ($title === '') $errors[] = 'Title is required.';
    if ($venueName === '') $errors[] = 'Venue name is required.';
    if ($competitionDate === '') $errors[] = 'Competition date is required.';
    if ($country === '') $errors[] = 'Country is required.';
    
    if (empty($errors)) {
      $stmt = $pdo->prepare("
        INSERT INTO competitions (
          club_id, title, venue_name, description, competition_date, start_time,
          address_line1, address_line2, town, county, postcode, country,
          latitude, longitude, visibility, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
      ");
      $stmt->execute([
        $clubId,
        $title,
        $venueName,
        $description ?: null,
        $competitionDate,
        $startTime ?: null,
        $addressLine1 ?: null,
        $addressLine2 ?: null,
        $town ?: null,
        $county ?: null,
        $postcode ?: null,
        $country,
        $latitude !== '' ? (float)$latitude : null,
        $longitude !== '' ? (float)$longitude : null,
        $visibility,
        $userId
      ]);
      $message = 'Competition created successfully.';
      $messageType = 'success';
    } else {
      $message = implode(' ', $errors);
      $messageType = 'danger';
    }
  }
  
  if ($action === 'delete' && isset($_POST['competition_id'])) {
    $compId = (int)$_POST['competition_id'];
    $stmt = $pdo->prepare("DELETE FROM competitions WHERE id = ? AND club_id = ?");
    $stmt->execute([$compId, $clubId]);
    $message = 'Competition deleted.';
    $messageType = 'info';
  }
}

$stmt = $pdo->prepare("
  SELECT * FROM competitions 
  WHERE club_id = ? 
  ORDER BY competition_date ASC
");
$stmt->execute([$clubId]);
$competitions = $stmt->fetchAll();

$upcomingCount = 0;
$today = date('Y-m-d');
foreach ($competitions as $c) {
  if ($c['competition_date'] >= $today) $upcomingCount++;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Competitions - <?= e($club['name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    #modalMap { height: 400px; border-radius: 8px; }
    .competition-card { border-left: 4px solid #0d6efd; }
    .competition-card.private { border-left-color: #6c757d; }
    .competition-card.past { opacity: 0.6; }
    .location-preview { background: #f8f9fa; padding: 10px; border-radius: 6px; }
  </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="/">Angling Club Manager</a>
    <div class="ms-auto">
      <a class="btn btn-outline-light btn-sm" href="/public/club.php?slug=<?= e($club['slug']) ?>">View Club</a>
      <a class="btn btn-outline-light btn-sm" href="/public/dashboard.php">Dashboard</a>
      <a class="btn btn-outline-light btn-sm" href="/public/auth/logout.php">Logout</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="mb-1">Competitions</h1>
      <p class="text-muted mb-0"><?= e($club['name']) ?></p>
    </div>
    <div>
      <span class="badge bg-primary fs-6"><?= $upcomingCount ?> Upcoming</span>
    </div>
  </div>

  <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
      <?= e($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="row">
    <div class="col-lg-5 mb-4">
      <div class="card">
        <div class="card-header bg-white">
          <h5 class="mb-0">Add New Competition</h5>
        </div>
        <div class="card-body">
          <form method="post" id="competitionForm">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="latitude" id="latitude">
            <input type="hidden" name="longitude" id="longitude">
            <input type="hidden" name="address_line1" id="address_line1">
            <input type="hidden" name="address_line2" id="address_line2">
            <input type="hidden" name="town" id="town">
            <input type="hidden" name="county" id="county">
            <input type="hidden" name="postcode" id="postcode">
            <input type="hidden" name="country" id="country" value="United Kingdom">
            
            <div class="mb-3">
              <label class="form-label">Title <span class="text-danger">*</span></label>
              <input type="text" name="title" class="form-control" required>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Venue Name <span class="text-danger">*</span></label>
              <input type="text" name="venue_name" class="form-control" required>
            </div>
            
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Date <span class="text-danger">*</span></label>
                <input type="date" name="competition_date" class="form-control" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Start Time</label>
                <input type="time" name="start_time" class="form-control">
              </div>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control" rows="2"></textarea>
            </div>
            
            <hr>
            <h6>Location</h6>
            
            <div class="mb-3">
              <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#mapModal">
                Select Location on Map
              </button>
            </div>
            
            <div id="locationPreview" class="location-preview mb-3" style="display: none;">
              <strong>Selected Location:</strong>
              <div id="previewAddress" class="small text-muted mt-1"></div>
              <div id="previewCoords" class="small text-muted"></div>
            </div>
            
            <hr>
            
            <div class="mb-3">
              <label class="form-label">Visibility</label>
              <select name="visibility" class="form-select">
                <option value="open">Open - Anyone can see</option>
                <option value="private">Private - Club members only</option>
              </select>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">Create Competition</button>
          </form>
        </div>
      </div>
    </div>
    
    <div class="col-lg-7">
      <?php if (empty($competitions)): ?>
        <div class="card">
          <div class="card-body text-center py-5">
            <h5 class="text-muted">No competitions yet</h5>
            <p class="text-muted">Add your first competition using the form.</p>
          </div>
        </div>
      <?php else: ?>
        <?php foreach ($competitions as $comp): ?>
          <?php
            $isPast = $comp['competition_date'] < $today;
            $isPrivate = $comp['visibility'] === 'private';
            $cardClass = 'competition-card';
            if ($isPast) $cardClass .= ' past';
            if ($isPrivate) $cardClass .= ' private';
          ?>
          <div class="card <?= $cardClass ?> mb-3">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <h5 class="mb-1">
                    <?= e($comp['title']) ?>
                    <?php if ($isPrivate): ?>
                      <span class="badge bg-secondary">Private</span>
                    <?php endif; ?>
                    <?php if ($isPast): ?>
                      <span class="badge bg-light text-dark">Past</span>
                    <?php endif; ?>
                  </h5>
                  <div class="text-muted mb-2">
                    <strong><?= e($comp['venue_name']) ?></strong>
                    <?php if ($comp['town']): ?>
                      &bull; <?= e($comp['town']) ?>
                    <?php endif; ?>
                    <?php if ($comp['country']): ?>
                      , <?= e($comp['country']) ?>
                    <?php endif; ?>
                  </div>
                  <div class="small">
                    <strong>Date:</strong> <?= date('l, j F Y', strtotime($comp['competition_date'])) ?>
                    <?php if ($comp['start_time']): ?>
                      at <?= date('g:i A', strtotime($comp['start_time'])) ?>
                    <?php endif; ?>
                  </div>
                  <?php if ($comp['description']): ?>
                    <div class="small text-muted mt-2"><?= e($comp['description']) ?></div>
                  <?php endif; ?>
                  <?php if ($comp['latitude'] && $comp['longitude']): ?>
                    <div class="small mt-2">
                      <a href="https://www.google.com/maps?q=<?= $comp['latitude'] ?>,<?= $comp['longitude'] ?>" target="_blank" class="text-decoration-none">
                        View on Google Maps
                      </a>
                    </div>
                  <?php endif; ?>
                </div>
                <div class="text-end">
                  <?php if ($comp['competition_date'] <= $today): ?>
                    <a href="/public/admin/competition_results.php?competition_id=<?= $comp['id'] ?>" class="btn btn-success btn-sm mb-1">
                      Results
                    </a>
                  <?php endif; ?>
                  <form method="post" class="d-inline" onsubmit="return confirm('Delete this competition?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="competition_id" value="<?= $comp['id'] ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                  </form>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="modal fade" id="mapModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Select Competition Location</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted mb-3">Click on the map to drop a pin at the competition location. You can drag the pin to adjust.</p>
        <div id="modalMap"></div>
        <div class="mt-3">
          <div id="modalAddressPreview" class="alert alert-info" style="display: none;">
            <strong>Address:</strong> <span id="modalAddressText"></span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="confirmLocationBtn" disabled>Confirm Location</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let map, marker;
let selectedLocation = null;
let selectedAddress = {};

const mapModal = document.getElementById('mapModal');
mapModal.addEventListener('shown.bs.modal', function() {
  if (!map) {
    initMap();
  } else {
    google.maps.event.trigger(map, 'resize');
  }
});

function initMap() {
  const defaultPos = { lat: 53.5, lng: -1.5 };
  
  map = new google.maps.Map(document.getElementById('modalMap'), {
    center: defaultPos,
    zoom: 6,
  });
  
  map.addListener('click', function(e) {
    placeMarker(e.latLng);
  });
}

function placeMarker(location) {
  if (marker) {
    marker.setPosition(location);
  } else {
    marker = new google.maps.Marker({
      position: location,
      map: map,
      draggable: true
    });
    
    marker.addListener('dragend', function() {
      updateLocation(marker.getPosition());
    });
  }
  updateLocation(location);
}

function updateLocation(location) {
  selectedLocation = {
    lat: location.lat(),
    lng: location.lng()
  };
  
  document.getElementById('confirmLocationBtn').disabled = false;
  reverseGeocode(location);
}

function reverseGeocode(location) {
  const geocoder = new google.maps.Geocoder();
  geocoder.geocode({ location: location }, function(results, status) {
    if (status === 'OK' && results[0]) {
      const components = results[0].address_components;
      selectedAddress = {
        address_line1: '',
        town: '',
        county: '',
        postcode: '',
        country: ''
      };
      
      for (const c of components) {
        if (c.types.includes('street_number') || c.types.includes('route')) {
          selectedAddress.address_line1 += (selectedAddress.address_line1 ? ' ' : '') + c.long_name;
        }
        if (c.types.includes('locality') || c.types.includes('postal_town')) {
          selectedAddress.town = c.long_name;
        }
        if (c.types.includes('administrative_area_level_2') || c.types.includes('administrative_area_level_1')) {
          selectedAddress.county = c.long_name;
        }
        if (c.types.includes('postal_code')) {
          selectedAddress.postcode = c.long_name;
        }
        if (c.types.includes('country')) {
          selectedAddress.country = c.long_name;
        }
      }
      
      const addressText = results[0].formatted_address;
      document.getElementById('modalAddressText').textContent = addressText;
      document.getElementById('modalAddressPreview').style.display = 'block';
    }
  });
}

document.getElementById('confirmLocationBtn').addEventListener('click', function() {
  if (!selectedLocation) return;
  
  document.getElementById('latitude').value = selectedLocation.lat;
  document.getElementById('longitude').value = selectedLocation.lng;
  document.getElementById('address_line1').value = selectedAddress.address_line1 || '';
  document.getElementById('town').value = selectedAddress.town || '';
  document.getElementById('county').value = selectedAddress.county || '';
  document.getElementById('postcode').value = selectedAddress.postcode || '';
  document.getElementById('country').value = selectedAddress.country || 'United Kingdom';
  
  const previewParts = [];
  if (selectedAddress.address_line1) previewParts.push(selectedAddress.address_line1);
  if (selectedAddress.town) previewParts.push(selectedAddress.town);
  if (selectedAddress.county) previewParts.push(selectedAddress.county);
  if (selectedAddress.postcode) previewParts.push(selectedAddress.postcode);
  if (selectedAddress.country) previewParts.push(selectedAddress.country);
  
  document.getElementById('previewAddress').textContent = previewParts.join(', ') || 'Location selected';
  document.getElementById('previewCoords').textContent = 
    'Coordinates: ' + selectedLocation.lat.toFixed(6) + ', ' + selectedLocation.lng.toFixed(6);
  document.getElementById('locationPreview').style.display = 'block';
  
  bootstrap.Modal.getInstance(document.getElementById('mapModal')).hide();
});
</script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&callback=initMap"></script>
</body>
</html>
