<?php
$meta = [
    'title' => 'Schedule Meeting | mybrandplease',
    'description' => 'Select a date and time for meeting.',
    'canonical' => 'meeting-schedule.php',
];
include 'includes/head.php';
include 'includes/header.php';

$timezoneList = DateTimeZone::listIdentifiers();
$defaultTimezone = isset($_GET['timezone']) && is_string($_GET['timezone']) ? trim($_GET['timezone']) : 'Asia/Kolkata';
if (!in_array($defaultTimezone, $timezoneList, true)) {
    $defaultTimezone = 'Asia/Kolkata';
}

function meeting_timezone_label(string $timezone): string
{
    $friendlyNames = [
        'Asia/Kolkata' => 'India Standard Time',
        'America/New_York' => 'Eastern Time',
        'America/Chicago' => 'Central Time',
        'America/Denver' => 'Mountain Time',
        'America/Los_Angeles' => 'Pacific Time',
        'Europe/London' => 'United Kingdom',
        'Australia/Sydney' => 'Australia - Sydney',
    ];
    $countryNames = [
        'AU' => 'Australia',
        'CA' => 'Canada',
        'DE' => 'Germany',
        'ES' => 'Spain',
        'FR' => 'France',
        'GB' => 'United Kingdom',
        'IN' => 'India',
        'IT' => 'Italy',
        'JP' => 'Japan',
        'NZ' => 'New Zealand',
        'US' => 'United States',
    ];
    $flags = [
        'AU' => '🇦🇺',
        'CA' => '🇨🇦',
        'DE' => '🇩🇪',
        'ES' => '🇪🇸',
        'FR' => '🇫🇷',
        'GB' => '🇬🇧',
        'IN' => '🇮🇳',
        'IT' => '🇮🇹',
        'JP' => '🇯🇵',
        'NZ' => '🇳🇿',
        'US' => '🇺🇸',
    ];

    $tzObject = new DateTimeZone($timezone);
    $now = new DateTimeImmutable('now', $tzObject);
    $offsetSeconds = $tzObject->getOffset($now);
    $offsetSign = $offsetSeconds >= 0 ? '+' : '-';
    $offsetSeconds = abs($offsetSeconds);
    $offset = 'UTC' . $offsetSign . sprintf('%02d:%02d', intdiv($offsetSeconds, 3600), intdiv($offsetSeconds % 3600, 60));
    $location = $tzObject->getLocation();
    $countryCode = is_array($location) ? (string) ($location['country_code'] ?? '') : '';
    $parts = explode('/', $timezone);
    $city = str_replace('_', ' ', (string) end($parts));
    $friendlyName = $friendlyNames[$timezone] ?? trim(($countryNames[$countryCode] ?? '') . ($countryCode !== '' ? ' - ' : '') . $city, ' -');

    if ($friendlyName === '') {
        $friendlyName = $city;
    }

    return trim(($flags[$countryCode] ?? '') . ' ' . $friendlyName) . ' (' . $offset . ')';
}
?>


<style>
:root {
    --primary-blue: #006bff;
    --text-dark: #1a1a1a;
    --text-muted: #666666;
    --border-color: #e6e6e6;
    --bg-light: #fcfcfc;
}


/* Main Container Wrapper */
.cal-section { padding: 50px 0; }
.cal-wrapper {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 20px rgba(0,0,0,0.08);
    border: 1px solid var(--border-color);
    overflow: visible; /* Needed for timezone dropdown positioning */
}

/* Sidebar Styling */
.cal-sidebar { padding: 40px; border-right: 1px solid var(--border-color); height: 100%; }
.cal-sidebar .brand-logo { max-width: 140px; margin-bottom: 25px; }
.cal-sidebar .meta-item { display: flex; align-items: start; color: var(--text-muted); margin-bottom: 12px; font-weight: 500; font-size: 15px; }
.cal-sidebar .meta-item i { width: 25px; margin-top: 4px; font-size: 16px; }

/* Calendar Content */
.cal-main-content { padding: 40px; }
.cal-nav-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
.month-display { font-weight: 600; font-size: 16px; color: var(--text-dark); }
.btn-nav { border: none; background: transparent; color: var(--primary-blue); font-size: 18px; width: 36px; height: 36px; border-radius: 50%; transition: 0.2s; }
.btn-nav:hover { background: #f0f7ff; }

.calendar-table { width: 100%; table-layout: fixed; }
.calendar-table th { text-align: center; font-size: 12px; color: var(--text-muted); padding-bottom: 15px; text-transform: uppercase; letter-spacing: 0.5px; }
.calendar-table td { text-align: center; padding: 4px; }

.day-btn {
    width: 42px; height: 42px; border: none; background: transparent; border-radius: 50%;
    font-size: 15px; font-weight: 600; color: var(--primary-blue); transition: 0.2s;
}
.day-btn:hover:not(:disabled) { background: #eef5ff; }
.day-btn.active { background: var(--primary-blue) !important; color: #fff !important; }
.day-btn:disabled { color: #ccc; cursor: not-allowed; }
.day-btn.today::after { content: ''; display: block; width: 4px; height: 4px; background: currentColor; border-radius: 50%; margin: 0 auto; }

/* Time Selection Styles */
.cal-time-sidebar { padding: 40px 25px; border-left: 1px solid var(--border-color); min-height: 550px; }
.time-title { font-weight: 600; margin-bottom: 20px; color: var(--text-dark); }
.time-scroll { max-height: 480px; overflow-y: auto; padding-right: 10px; }
.form-label{font-weight: 600; font-size: 16px; color: var(--text-dark);}
.time-slot-container { display: flex; gap: 8px; margin-bottom: 10px; align-items: center; }

/* Default time button state */
.time-btn-single {
    width: 100%; padding: 14px; background: #fff; border: 1px solid var(--border-color);
    color: var(--primary-blue); border-radius: 4px; font-weight: 700; transition: 0.2s; cursor: pointer;
}
.time-btn-single:hover { background: #f0f7ff; border-color: var(--primary-blue); }

/* Selected state - split layout */
.time-btn-left { 
    width: 48%; background: #555; color: #fff; border: none; border-radius: 4px; padding: 14px; 
    font-weight: 700; text-align: center; cursor: default;
}

.btn-confirm-next { 
    width: 48%; background: var(--primary-blue); color: #fff; border: none; border-radius: 4px; 
    padding: 14px; font-weight: 700; transition: all 0.2s ease; cursor: pointer; font-size: 16px;
}

.btn-confirm-next:hover { 
    background: #0056cc; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0, 107, 255, 0.3);
}

@keyframes slideIn { from { opacity: 0; transform: translateX(10px); } to { opacity: 1; transform: translateX(0); } }

.time-slot-container.selected .time-btn-left {
    animation: slideIn 0.2s ease-out;
}

.time-slot-container.selected .btn-confirm-next {
    animation: slideIn 0.2s ease-out;
}

/* Searchable Timezone Dropdown */
.cal-tz-wrap { margin-top: 30px; position: relative; }
.tz-trigger {
    display: flex; align-items: center; gap: 8px; padding: 10px; border: 1px solid #dee2e6;
    border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 500; color:black;
}
.tz-trigger span { line-height: 1.35; }
.tz-dropdown {
    position: absolute; bottom: 100%; left: 0; width: 100%; min-width: 280px;
    background: #fff; border: 1px solid #e1e1e1; border-radius: 8px;
    box-shadow: 0 -10px 25px rgba(0,0,0,0.1); z-index: 1000; margin-bottom: 5px;
}
.tz-list { max-height: 260px; overflow-y: auto; }
.tz-item { display: flex; flex-direction: column; gap: 2px; padding: 10px; font-size: 13px; cursor: pointer; border-radius: 4px;color:black; }
.tz-item__name { font-weight: 700; line-height: 1.25; }
.tz-item__id { color: var(--text-muted); font-size: 12px; line-height: 1.25; word-break: break-word; }
.tz-item:hover { background: #f0f7ff; color: var(--primary-blue); }
.tz-item.active { background: var(--primary-blue); color: #fff; }
.tz-item.active .tz-item__id { color: rgba(255, 255, 255, 0.82); }

.time-scroll::-webkit-scrollbar, .tz-list::-webkit-scrollbar { width: 5px; }
.time-scroll::-webkit-scrollbar-thumb, .tz-list::-webkit-scrollbar-thumb { background: #d1d1d1; border-radius: 10px; }

@media (max-width: 991px) {
    .cal-sidebar, .cal-time-sidebar { border: none; border-bottom: 1px solid var(--border-color); }
}
</style>

<div class="breadcumb">
  <div class="container rr-container-1895">
    <div class="breadcumb-wrapper section-spacing-120 fix" data-bg-src="assets/imgs/breadcumbBg.jpg">
      <div class="breadcumb-wrapper__title">Book a call with us</div>
      <ul class="breadcumb-wrapper__items">
        <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-house"></i></li>
        <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-chevron-right"></i></li>
        <li class="breadcumb-wrapper__items-list">
          <a href="<?php echo htmlspecialchars(url('index.php'), ENT_QUOTES, 'UTF-8'); ?>" class="breadcumb-wrapper__items-list-title">Home</a>
        </li>
        <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-chevron-right"></i></li>
        <li class="breadcumb-wrapper__items-list">
          <span class="breadcumb-wrapper__items-list-title2">Book a call with us</span>
        </li>
      </ul>
    </div>
  </div>
</div>

<section class="cal-section">
    <div class="container" >
        <form action="<?php echo htmlspecialchars(url('meeting-details'), ENT_QUOTES, 'UTF-8'); ?>" method="GET" class="cal-wrapper shadow-sm">
            <div class="row g-0">
                
                <div class="col-lg-4">
                    <div class="cal-sidebar">
                        <img src="<?php echo url('uploads/logo/mybrandplease-1.gif'); ?>" alt="Logo" class="brand-logo">
                        <hr class="my-4" style="opacity: 0.1;">
                        <p class="text-muted small fw-bold mb-1">NIMISHA IMPEX WORLDWIDE (P) LIMITED</p>
                        <h2 class="h4 text-muted fw-bold mb-4"><span class="thirty-minute">30</span> Minute Meeting</h2>
                        <div class="meta-item">
                            <i class="fa-regular fa-clock"></i>
                            <span>30 min</span>
                        </div>
                        <div class="meta-item">
                            <i class="fa-solid fa-video"></i>
                            <span>Web conferencing details provided upon confirmation.</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="cal-main-content">
                        <h5 class="fw-bold mb-4">Select a Date & Time</h5>
                        <div class="cal-nav-row">
                            <button type="button" class="btn-nav" id="prevMonth"><i class="fa-solid fa-chevron-left"></i></button>
                            <div class="month-display" id="monthLabel"></div>
                            <button type="button" class="btn-nav" id="nextMonth"><i class="fa-solid fa-chevron-right"></i></button>
                        </div>

                        <table class="calendar-table">
                            <thead>
                                <tr><th>MON</th><th>TUE</th><th>WED</th><th>THU</th><th>FRI</th><th>SAT</th><th>SUN</th></tr>
                            </thead>
                            <tbody id="calGrid"></tbody>
                        </table>

                        <div class="cal-tz-wrap">
                            <label class="form-label" for="tzSearch">Time Zone *</label>
                            <div class="tz-trigger" id="tzTrigger">
                                <i class="fa-solid fa-earth-americas me-2"></i>
                                <span id="tzCurrentName"><?= htmlspecialchars(meeting_timezone_label($defaultTimezone), ENT_QUOTES, 'UTF-8') ?></span>
                                <i class="fa-solid fa-caret-up ms-auto"></i>
                            </div>

                            <div class="tz-dropdown d-none" id="tzDropdown">
                                <div class="p-3">
                                    <input type="text" class="form-control form-control-sm mb-2" id="tzSearch" placeholder="Search...">
                                    <div class="tz-list" id="tzList">
                                        <?php foreach ($timezoneList as $tz): ?>
                                            <?php $tzLabel = meeting_timezone_label($tz); ?>
                                            <?php $tzSearch = strtolower(str_replace(['_', '/'], [' ', ' '], $tz . ' ' . $tzLabel . ' ' . preg_replace('/UTC([+-])0(\d:)/', 'UTC$1$2', $tzLabel))); ?>
                                            <div class="tz-item <?= $tz === $defaultTimezone ? 'active' : '' ?>" data-tz="<?= htmlspecialchars($tz, ENT_QUOTES, 'UTF-8') ?>" data-label="<?= htmlspecialchars($tzLabel, ENT_QUOTES, 'UTF-8') ?>" data-search="<?= htmlspecialchars($tzSearch, ENT_QUOTES, 'UTF-8') ?>">
                                                <span class="tz-item__name"><?= htmlspecialchars($tzLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                                <span class="tz-item__id"><?= htmlspecialchars($tz, ENT_QUOTES, 'UTF-8') ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3">
                    <div class="cal-time-sidebar">
                        <div class="time-title" id="selectedDateLabel"></div>
                        <div class="time-scroll" id="timeList"></div>
                    </div>
                </div>

            </div>

            <input type="hidden" name="date" id="selectedDateValue">
            <input type="hidden" name="time" id="selectedTimeValue">
            <input type="hidden" name="timezone" id="selectedTz" value="<?= htmlspecialchars($defaultTimezone, ENT_QUOTES, 'UTF-8') ?>" required>
        </form>
    </div>
</section>

<script>
(() => {
    const monthLabel = document.getElementById('monthLabel');
    const calGrid = document.getElementById('calGrid');
    const timeList = document.getElementById('timeList');
    const dateLabel = document.getElementById('selectedDateLabel');
    const dateInput = document.getElementById('selectedDateValue');
    const timeInput = document.getElementById('selectedTimeValue');

    let now = new Date();
    let viewYear = now.getFullYear();
    let viewMonth = now.getMonth();
    let selectedDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    let selectedTime = null;

    const timeSlots = ['12:00am', '12:30am', '1:00am', '1:30am', '2:00am', '6:30pm', '7:00pm', '7:30pm', '8:00pm'];

    function to24Hour(value) {
        const m = String(value).trim().toLowerCase().match(/^(\d{1,2}):(\d{2})(am|pm)$/);
        if (!m) return '';
        let h = parseInt(m[1], 10);
        const min = m[2];
        const suffix = m[3];
        if (suffix === 'pm' && h !== 12) h += 12;
        if (suffix === 'am' && h === 12) h = 0;
        return String(h).padStart(2, '0') + ':' + min;
    }

    function formatLocalDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function renderCalendar() {
        monthLabel.textContent = new Date(viewYear, viewMonth).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        dateLabel.textContent = selectedDate.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });
        dateInput.value = formatLocalDate(selectedDate);
        
        calGrid.innerHTML = '';
        let firstDay = (new Date(viewYear, viewMonth, 1).getDay() + 6) % 7;
        let daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();
        
        let row = document.createElement('tr');
        for (let i = 0; i < firstDay; i++) row.appendChild(document.createElement('td'));

        for (let d = 1; d <= daysInMonth; d++) {
            if (row.children.length === 7) {
                calGrid.appendChild(row);
                row = document.createElement('tr');
            }
            let cell = document.createElement('td');
            let btn = document.createElement('button');
            btn.type = 'button'; btn.className = 'day-btn'; btn.textContent = d;

            let checkDate = new Date(viewYear, viewMonth, d);
            if (checkDate < new Date().setHours(0,0,0,0)) btn.disabled = true;
            if (checkDate.toDateString() === new Date().toDateString()) btn.classList.add('today');
            if (checkDate.toDateString() === selectedDate.toDateString()) btn.classList.add('active');

            btn.onclick = () => { 
                selectedDate = checkDate;
                selectedTime = null; // Reset time when date changes
                renderCalendar(); 
                renderTimeSlots(); 
            };
            cell.appendChild(btn);
            row.appendChild(cell);
        }
        calGrid.appendChild(row);
    }

    function renderTimeSlots() {
        timeList.innerHTML = '';
        timeSlots.forEach((t, idx) => {
            const container = document.createElement('div');
            container.className = 'time-slot-container';

            if (selectedTime === t) {
                // Show selected state with split buttons
                const leftBtn = document.createElement('div');
                leftBtn.className = 'time-btn-left';
                leftBtn.textContent = t;

                const nextBtn = document.createElement('button');
                nextBtn.type = 'submit';
                nextBtn.className = 'btn-confirm-next';
                nextBtn.textContent = 'Next';
                
                timeInput.value = to24Hour(t);
                container.classList.add('selected');
                container.appendChild(leftBtn);
                container.appendChild(nextBtn);
            } else {
                // Show normal button state
                const mainBtn = document.createElement('button');
                mainBtn.type = 'button';
                mainBtn.className = 'time-btn-single';
                mainBtn.textContent = t;

                mainBtn.onclick = () => {
                    selectedTime = t; // Store selected time
                    renderTimeSlots(); // Re-render to show selected state
                };

                container.appendChild(mainBtn);
            }
            
            timeList.appendChild(container);
        });
    }

    // Timezone Logic
    const tzTrigger = document.getElementById('tzTrigger');
    const tzDropdown = document.getElementById('tzDropdown');
    const tzSearch = document.getElementById('tzSearch');
    const tzItems = document.querySelectorAll('.tz-item');
    const selectedTzInput = document.getElementById('selectedTz');
    const tzCurrentName = document.getElementById('tzCurrentName');

    function selectTimezone(item) {
        if (!item) return;
        const val = item.getAttribute('data-tz');
        tzCurrentName.textContent = item.getAttribute('data-label') || val;
        selectedTzInput.value = val;
        tzItems.forEach(i => i.classList.remove('active'));
        item.classList.add('active');
    }

    tzTrigger.onclick = () => {
        tzDropdown.classList.toggle('d-none');
        if (!tzDropdown.classList.contains('d-none')) {
            tzSearch.focus();
        }
    };
    
    tzSearch.oninput = (e) => {
        const val = e.target.value.toLowerCase().replace(/\s+/g, ' ').trim();
        tzItems.forEach(item => {
            item.style.display = item.getAttribute('data-search').includes(val) ? 'flex' : 'none';
        });
    };

    tzItems.forEach(item => {
        item.onclick = () => {
            selectTimezone(item);
            tzDropdown.classList.add('d-none');
        };
    });

    try {
        const detectedTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        const detectedItem = detectedTimezone
            ? Array.from(tzItems).find(item => item.getAttribute('data-tz') === detectedTimezone)
            : null;
        if (detectedItem) selectTimezone(detectedItem);
    } catch (error) {}

    document.getElementById('prevMonth').onclick = () => { viewMonth--; renderCalendar(); };
    document.getElementById('nextMonth').onclick = () => { viewMonth++; renderCalendar(); };

    renderCalendar();
    renderTimeSlots();
})();
</script>

<?php include 'includes/footer.php'; ?>
