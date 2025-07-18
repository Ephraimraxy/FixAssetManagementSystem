<?php
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get user settings
$stmt = $pdo->prepare('SELECT * FROM user_settings WHERE user_id = ?');
$stmt->execute([$user_id]);
$settings = $stmt->fetch();

// If no settings exist, create default settings
if (!$settings) {
    $stmt = $pdo->prepare('INSERT INTO user_settings (user_id, email_notifications, dashboard_view, theme) VALUES (?, 1, "grid", "light")');
    $stmt->execute([$user_id]);
    
    // Get the newly created settings
    $stmt = $pdo->prepare('SELECT * FROM user_settings WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $settings = $stmt->fetch();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $dashboard_view = $_POST['dashboard_view'] ?? 'grid';
    $theme = $_POST['theme'] ?? 'light';
    
    // Update settings
    $stmt = $pdo->prepare('
        UPDATE user_settings 
        SET email_notifications = ?, dashboard_view = ?, theme = ?, updated_at = NOW() 
        WHERE user_id = ?
    ');
    
    if ($stmt->execute([$email_notifications, $dashboard_view, $theme, $user_id])) {
        $success_message = 'Settings updated successfully.';
        
        // Refresh settings data
        $stmt = $pdo->prepare('SELECT * FROM user_settings WHERE user_id = ?');
        $stmt->execute([$user_id]);
        $settings = $stmt->fetch();
    } else {
        $error_message = 'An error occurred while updating your settings.';
    }
}

// Process notification settings form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_notifications'])) {
    $maintenance_updates = isset($_POST['maintenance_updates']) ? 1 : 0;
    $asset_updates = isset($_POST['asset_updates']) ? 1 : 0;
    $system_announcements = isset($_POST['system_announcements']) ? 1 : 0;
    $depreciation_alerts = isset($_POST['depreciation_alerts']) ? 1 : 0;
    
    // Update notification settings
    $stmt = $pdo->prepare('
        UPDATE user_settings 
        SET 
            maintenance_updates = ?, 
            asset_updates = ?, 
            system_announcements = ?, 
            depreciation_alerts = ?, 
            updated_at = NOW() 
        WHERE user_id = ?
    ');
    
    if ($stmt->execute([$maintenance_updates, $asset_updates, $system_announcements, $depreciation_alerts, $user_id])) {
        $success_message = 'Notification settings updated successfully.';
        
        // Refresh settings data
        $stmt = $pdo->prepare('SELECT * FROM user_settings WHERE user_id = ?');
        $stmt->execute([$user_id]);
        $settings = $stmt->fetch();
    } else {
        $error_message = 'An error occurred while updating your notification settings.';
    }
}

// Process export data request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_data'])) {
    $export_type = $_POST['export_type'] ?? '';
    
    if ($export_type === 'assets' || $export_type === 'maintenance' || $export_type === 'all') {
        // This would normally generate a CSV file, but for demonstration we'll just set a success message
        $success_message = 'Data export has been initiated. You will receive an email when it\'s ready to download.';
    } else {
        $error_message = 'Please select a valid data type to export.';
    }
}

require_once '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="mb-0"><i class="fas fa-cog me-2 text-primary"></i> Settings</h1>
            <p class="text-muted">Customize your fixed asset management experience</p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="profile.php" class="btn btn-outline-primary">
                <i class="fas fa-user-circle me-2"></i> Back to Profile
            </a>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="list-group shadow-sm sticky-top" style="top: 20px;">
                <a href="#appearance" class="list-group-item list-group-item-action d-flex align-items-center" data-bs-toggle="list">
                    <i class="fas fa-palette me-3 text-primary"></i> Appearance
                </a>
                <a href="#notifications" class="list-group-item list-group-item-action d-flex align-items-center" data-bs-toggle="list">
                    <i class="fas fa-bell me-3 text-primary"></i> Notifications
                </a>
                <a href="#data" class="list-group-item list-group-item-action d-flex align-items-center" data-bs-toggle="list">
                    <i class="fas fa-database me-3 text-primary"></i> Data Management
                </a>
                <a href="#privacy" class="list-group-item list-group-item-action d-flex align-items-center" data-bs-toggle="list">
                    <i class="fas fa-shield-alt me-3 text-primary"></i> Privacy
                </a>
                <a href="#help" class="list-group-item list-group-item-action d-flex align-items-center" data-bs-toggle="list">
                    <i class="fas fa-question-circle me-3 text-primary"></i> Help & Support
                </a>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="tab-content">
                <!-- Appearance Tab -->
                <div class="tab-pane fade show active" id="appearance">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0"><i class="fas fa-palette me-2"></i> Appearance Settings</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="settings.php">
                                <input type="hidden" name="update_settings" value="1">
                                
                                <div class="mb-4">
                                    <label class="form-label">Theme</label>
                                    <div class="row">
                                        <div class="col-md-4 col-sm-6 mb-3">
                                            <div class="form-check custom-option">
                                                <input type="radio" class="form-check-input" id="theme_light" name="theme" value="light" 
                                                       <?php echo ($settings['theme'] === 'light') ? 'checked' : ''; ?>>
                                                <label class="form-check-label custom-option-content p-3" for="theme_light">
                                                    <div class="text-center">
                                                        <div class="mb-2 p-3 bg-light border rounded">
                                                            <i class="fas fa-sun fa-2x text-warning"></i>
                                                        </div>
                                                        <h6 class="mb-0">Light</h6>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-sm-6 mb-3">
                                            <div class="form-check custom-option">
                                                <input type="radio" class="form-check-input" id="theme_dark" name="theme" value="dark" 
                                                       <?php echo ($settings['theme'] === 'dark') ? 'checked' : ''; ?>>
                                                <label class="form-check-label custom-option-content p-3" for="theme_dark">
                                                    <div class="text-center">
                                                        <div class="mb-2 p-3 bg-dark border rounded">
                                                            <i class="fas fa-moon fa-2x text-light"></i>
                                                        </div>
                                                        <h6 class="mb-0">Dark</h6>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-sm-6 mb-3">
                                            <div class="form-check custom-option">
                                                <input type="radio" class="form-check-input" id="theme_auto" name="theme" value="auto" 
                                                       <?php echo ($settings['theme'] === 'auto') ? 'checked' : ''; ?>>
                                                <label class="form-check-label custom-option-content p-3" for="theme_auto">
                                                    <div class="text-center">
                                                        <div class="mb-2 p-3 bg-primary border rounded">
                                                            <i class="fas fa-adjust fa-2x text-white"></i>
                                                        </div>
                                                        <h6 class="mb-0">Auto</h6>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">Dashboard View</label>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check custom-option">
                                                <input type="radio" class="form-check-input" id="dashboard_grid" name="dashboard_view" value="grid" 
                                                       <?php echo ($settings['dashboard_view'] === 'grid') ? 'checked' : ''; ?>>
                                                <label class="form-check-label custom-option-content p-3" for="dashboard_grid">
                                                    <div class="d-flex align-items-start">
                                                        <div class="pe-3">
                                                            <i class="fas fa-th fa-2x text-primary"></i>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-1">Grid View</h6>
                                                            <p class="text-muted small mb-0">Cards arranged in a grid for easy scanning</p>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check custom-option">
                                                <input type="radio" class="form-check-input" id="dashboard_list" name="dashboard_view" value="list" 
                                                       <?php echo ($settings['dashboard_view'] === 'list') ? 'checked' : ''; ?>>
                                                <label class="form-check-label custom-option-content p-3" for="dashboard_list">
                                                    <div class="d-flex align-items-start">
                                                        <div class="pe-3">
                                                            <i class="fas fa-list fa-2x text-primary"></i>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-1">List View</h6>
                                                            <p class="text-muted small mb-0">Compact list view for more information at once</p>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i> Save Appearance Settings
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Notifications Tab -->
                <div class="tab-pane fade" id="notifications">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0"><i class="fas fa-bell me-2"></i> Notification Settings</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="settings.php">
                                <input type="hidden" name="update_notifications" value="1">
                                
                                <div class="form-check form-switch mb-4">
                                    <input class="form-check-input" type="checkbox" role="switch" id="email_notifications" name="email_notifications" 
                                           <?php echo ($settings['email_notifications'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="email_notifications">
                                        <strong>Email Notifications</strong>
                                        <p class="text-muted small mb-0">Receive notifications via email</p>
                                    </label>
                                </div>
                                
                                <hr class="my-4">
                                
                                <h6 class="mb-3">Notification Preferences</h6>
                                
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" role="switch" id="maintenance_updates" name="maintenance_updates" 
                                           <?php echo ($settings['maintenance_updates'] ?? 1) == 1 ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="maintenance_updates">
                                        Maintenance Request Updates
                                    </label>
                                </div>
                                
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" role="switch" id="asset_updates" name="asset_updates" 
                                           <?php echo ($settings['asset_updates'] ?? 1) == 1 ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="asset_updates">
                                        Asset Request Updates
                                    </label>
                                </div>
                                
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" role="switch" id="system_announcements" name="system_announcements" 
                                           <?php echo ($settings['system_announcements'] ?? 1) == 1 ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="system_announcements">
                                        System Announcements
                                    </label>
                                </div>
                                
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" role="switch" id="depreciation_alerts" name="depreciation_alerts" 
                                           <?php echo ($settings['depreciation_alerts'] ?? 1) == 1 ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="depreciation_alerts">
                                        Asset Depreciation Alerts
                                    </label>
                                </div>
                                
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i> Save Notification Settings
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Data Management Tab -->
                <div class="tab-pane fade" id="data">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0"><i class="fas fa-database me-2"></i> Data Management</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <h6>Export Your Data</h6>
                                <p class="text-muted">Download your data in CSV format for your records or analysis.</p>
                                
                                <form method="post" action="settings.php" class="mt-3">
                                    <input type="hidden" name="export_data" value="1">
                                    
                                    <div class="mb-3">
                                        <label for="export_type" class="form-label">Select Data to Export</label>
                                        <select class="form-select" id="export_type" name="export_type" required>
                                            <option value="">-- Select Data Type --</option>
                                            <option value="assets">My Assets</option>
                                            <option value="maintenance">Maintenance Requests</option>
                                            <option value="all">All My Data</option>
                                        </select>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-file-export me-2"></i> Export Data
                                    </button>
                                </form>
                            </div>
                            
                            <hr class="my-4">
                            
                            <div>
                                <h6>Data Retention</h6>
                                <p class="text-muted">Your asset and maintenance data is stored according to company policy. Contact your administrator for more information.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Privacy Tab -->
                <div class="tab-pane fade" id="privacy">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i> Privacy Settings</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-4">We're committed to protecting your privacy and ensuring your data is secure.</p>
                            
                            <div class="mb-4">
                                <h6>Your Privacy Rights</h6>
                                <ul class="text-muted">
                                    <li>You have the right to access your personal data</li>
                                    <li>You can request correction of your personal data</li>
                                    <li>You can contact the data protection officer for any concerns</li>
                                </ul>
                            </div>
                            
                            <div class="mb-4">
                                <h6>Data Sharing</h6>
                                <p class="text-muted">Your data is shared only with authorized personnel within your organization who need access to manage assets effectively.</p>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                                <a href="#" class="btn btn-outline-primary">
                                    <i class="fas fa-file-alt me-2"></i> Privacy Policy
                                </a>
                                <a href="#" class="btn btn-outline-secondary">
                                    <i class="fas fa-gavel me-2"></i> Terms of Use
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Help & Support Tab -->
                <div class="tab-pane fade" id="help">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0"><i class="fas fa-question-circle me-2"></i> Help & Support</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="card border-0 mb-3">
                                        <div class="card-body bg-light rounded">
                                            <h6 class="mb-2"><i class="fas fa-book me-2 text-primary"></i> Documentation</h6>
                                            <p class="text-muted small mb-3">Access user guides and documentation to learn how to use the system effectively.</p>
                                            <a href="#" class="btn btn-sm btn-outline-primary">View Documentation</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-0 mb-3">
                                        <div class="card-body bg-light rounded">
                                            <h6 class="mb-2"><i class="fas fa-video me-2 text-primary"></i> Video Tutorials</h6>
                                            <p class="text-muted small mb-3">Watch step-by-step video tutorials to quickly master system features.</p>
                                            <a href="#" class="btn btn-sm btn-outline-primary">Watch Tutorials</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <h6 class="mb-3">Frequently Asked Questions</h6>
                            
                            <div class="accordion mb-4" id="faqAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="faqOne">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                            How do I request a new asset?
                                        </button>
                                    </h2>
                                    <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="faqOne" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body text-muted">
                                            To request a new asset, navigate to the "Asset Request" page from the dashboard. Fill out the request form with details about the asset you need and why you need it. Your request will be sent to the appropriate administrator for review.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="faqTwo">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                            How do I report an issue with an asset?
                                        </button>
                                    </h2>
                                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="faqTwo" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body text-muted">
                                            To report an issue with an asset, go to the "Maintenance Request" page and select the asset that needs attention. Provide details about the issue, set the priority level, and submit the request. The maintenance team will be notified.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="faqThree">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                            How is asset depreciation calculated?
                                        </button>
                                    </h2>
                                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="faqThree" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body text-muted">
                                            Asset depreciation is calculated based on the method set by your organization's accounting policies. The system supports straight-line, declining balance, and sum-of-years'-digits methods. The specifics for each asset can be viewed on the asset details page.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center">
                                <h6>Need More Help?</h6>
                                <p class="text-muted">Contact our support team for assistance.</p>
                                <a href="mailto:support@example.com" class="btn btn-primary">
                                    <i class="fas fa-envelope me-2"></i> Contact Support
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.custom-option {
    padding: 0;
    margin: 0;
}

.custom-option-content {
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.custom-option-content:hover {
    border-color: #adb5bd;
}

.form-check-input:checked + .custom-option-content {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.list-group-item-action.active {
    background-color: #f8f9fa;
    color: #0d6efd;
    border-color: #dee2e6;
    border-left: 3px solid #0d6efd;
    font-weight: 500;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle tab links
    const hash = window.location.hash;
    if (hash) {
        const tab = document.querySelector(`a[href="${hash}"]`);
        if (tab) {
            tab.click();
        }
    }
    
    // Update URL hash on tab click
    const tabLinks = document.querySelectorAll('.list-group-item');
    tabLinks.forEach(link => {
        link.addEventListener('shown.bs.tab', function(e) {
            window.location.hash = e.target.getAttribute('href');
        });
        
        // Add active class to list group items when clicked
        link.addEventListener('click', function() {
            tabLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
        });
    });
    
    // Set first tab as active by default if no hash
    if (!hash) {
        tabLinks[0].classList.add('active');
    } else {
        const activeTab = document.querySelector(`a[href="${hash}"]`);
        if (activeTab) {
            activeTab.classList.add('active');
        } else {
            tabLinks[0].classList.add('active');
        }
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
