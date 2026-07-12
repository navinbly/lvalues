# LVALUES Admin Dashboard automated smoke test (TC-01..TC-16 subset)
# Usage:  powershell -ExecutionPolicy Bypass -File docs\test_admin_dashboard.ps1
# Optional params let you point at staging/production with a test admin account.
param(
    [string]$BaseUrl = "http://localhost/lvalues",
    [string]$AdminEmail = "admin@gmail.com",
    [string]$AdminPassword = "1234"
)

$ErrorActionPreference = 'Continue'
$results = @()
function Record($id, $name, $pass, $detail = '') {
    $script:results += [pscustomobject]@{ ID = $id; Test = $name; Result = $(if ($pass) { 'PASS' } else { 'FAIL' }); Detail = $detail }
}

# ---------- TC-02: dashboard blocked when logged out ----------
try {
    $anonDash = Invoke-WebRequest -Uri "$BaseUrl/admin/dashboard" -UseBasicParsing
    Record 'TC-02' 'Dashboard blocked when logged out' ($anonDash.Content -notmatch 'Operations command center') ''
} catch { Record 'TC-02' 'Dashboard blocked when logged out' $true 'request rejected' }

# ---------- TC-03: CSRF enforced ----------
$csrfBlocked = $false
try { $null = Invoke-WebRequest -Uri "$BaseUrl/login/validate_login" -Method POST -Body @{ email = $AdminEmail; password = $AdminPassword } -UseBasicParsing } catch { $csrfBlocked = $true }
Record 'TC-03' 'Login POST without CSRF token rejected' $csrfBlocked ''

# ---------- TC-01: login ----------
$s = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$null = Invoke-WebRequest -Uri "$BaseUrl/login" -WebSession $s -UseBasicParsing
$csrf = ($s.Cookies.GetCookies("$BaseUrl/") | Where-Object { $_.Name -eq 'lvalues_csrf_cookie' }).Value
$null = Invoke-WebRequest -Uri "$BaseUrl/login/validate_login" -Method POST -Body @{ email = $AdminEmail; password = $AdminPassword; lvalues_csrf_token = $csrf } -WebSession $s -UseBasicParsing
$sw = [System.Diagnostics.Stopwatch]::StartNew()
$dash = Invoke-WebRequest -Uri "$BaseUrl/admin/dashboard" -WebSession $s -UseBasicParsing
$sw.Stop()
Record 'TC-01' 'Admin login + dashboard loads' ($dash.StatusCode -eq 200 -and $dash.Content.Contains('Operations command center')) "$($sw.ElapsedMilliseconds) ms"

# ---------- TC-04: command center tiles ----------
$tiles = @('Tutor applications', 'Course approvals', 'Books/articles review', 'Payout exceptions', 'Question bank', 'Exam/mock tests')
$missing = $tiles | Where-Object { -not $dash.Content.Contains($_) }
Record 'TC-04' 'Command center metric tiles render' ($missing.Count -eq 0) $(if ($missing) { "missing: $($missing -join ', ')" })

# ---------- TC-08: dashboard performance ----------
Record 'TC-08' 'Dashboard loads under 4s' ($sw.ElapsedMilliseconds -lt 4000) "$($sw.ElapsedMilliseconds) ms"

# ---------- TC-09/TC-10: sidebar structure ----------
Record 'TC-09' 'Sidebar has Content Studio + Exams & Mock Tests' ($dash.Content.Contains('Content Studio') -and $dash.Content.Contains('Exams & Mock Tests')) ''
$staleItems = @('Publish Books', 'navigation_alias/analytics_growth', 'navigation_alias/learning_certificates', 'navigation_alias/support_complaints', 'navigation_alias/content_seo') | Where-Object { $dash.Content.Contains($_) }
Record 'TC-10' 'Dead/misleading nav items removed' ($staleItems.Count -eq 0) $(if ($staleItems) { "still present: $($staleItems -join ', ')" })

# ---------- TC-21: all key admin pages render ----------
$pages = @('admin/users', 'admin/instructors', 'admin/content_nodes', 'admin/content_nodes_pending', 'admin/question_bank', 'admin/content_public_exams', 'admin/exam_pattern_builder', 'admin/exam_analytics', 'admin/moderation_center', 'admin/system_settings')
$broken = @()
foreach ($p in $pages) {
    try {
        $r = Invoke-WebRequest -Uri "$BaseUrl/$p" -WebSession $s -UseBasicParsing -TimeoutSec 90
        if ($r.StatusCode -ne 200 -or $r.Content -match '(A PHP Error|Fatal error)') { $broken += $p }
    } catch { $broken += $p }
}
Record 'TC-21' 'All key admin pages render without PHP errors' ($broken.Count -eq 0) $(if ($broken) { "broken: $($broken -join ', ')" })

# ---------- TC-18: AJAX question pool endpoint ----------
try {
    $pool = Invoke-WebRequest -Uri "$BaseUrl/admin/question_bank_pool?exam=&topic=&difficulty=" -WebSession $s -UseBasicParsing
    $poolJson = $pool.Content | ConvertFrom-Json
    Record 'TC-18' 'Question pool AJAX endpoint returns JSON' ($pool.StatusCode -eq 200 -and $null -ne $poolJson.total) "total=$($poolJson.total)"
} catch { Record 'TC-18' 'Question pool AJAX endpoint returns JSON' $false $_.Exception.Message }

# ---------- TC-13..15: builder options ----------
$builder = Invoke-WebRequest -Uri "$BaseUrl/admin/exam_pattern_builder" -WebSession $s -UseBasicParsing
Record 'TC-13' 'Builder offers Publish Now option' ($builder.Content.Contains('Publish Now (live immediately)')) ''
Record 'TC-15' 'Exam list offers Publish/Unpublish actions' (($dashList = Invoke-WebRequest -Uri "$BaseUrl/admin/content_public_exams" -WebSession $s -UseBasicParsing).Content -match 'exam_pattern_(publish|unpublish)' -or $dashList.Content.Contains('No exams found')) ''

# ---------- TC-22: public site unaffected ----------
$publicBroken = @()
foreach ($p in @('', 'mock-tests', 'books', 'blog', 'home/courses')) {
    try { $r = Invoke-WebRequest -Uri "$BaseUrl/$p" -UseBasicParsing -TimeoutSec 60; if ($r.StatusCode -ne 200) { $publicBroken += "/$p" } } catch { $publicBroken += "/$p" }
}
Record 'TC-22' 'Public pages render anonymously' ($publicBroken.Count -eq 0) $(if ($publicBroken) { "broken: $($publicBroken -join ', ')" })

# ---------- Report ----------
""
$results | Format-Table -AutoSize
$failCount = ($results | Where-Object { $_.Result -eq 'FAIL' }).Count
""
if ($failCount -eq 0) { "ALL $($results.Count) CHECKS PASSED" } else { "$failCount of $($results.Count) checks FAILED" }
exit $failCount
