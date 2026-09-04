<?php
/**
 * BMS Campus Digital Signage Display - White Theme
 * Layout: Timetable 55% - Left Side 45%
 * Reduced brochure and card height
 * 
 * Updated: Loads 'type' column from class_reservations
 * Updated: Shows type badge in Programme column (except Lecture and Guest Lecture)
 * Updated: Time in 12-hour format (without AM/PM text)
 * Updated: Classroom names show with floor information
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>BMS Digital Signage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        html, body {
            height: 100vh;
            width: 100vw;
            overflow: hidden;
        }
        
        body {
            font-family: 'Segoe UI', Roboto, system-ui, -apple-system, sans-serif;
            background: #f0f2f5;
            color: #1a1a2e;
        }
        
        .dashboard {
            display: flex;
            height: 100vh;
            width: 100vw;
            padding: 16px;
            gap: 16px;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
            box-sizing: border-box;
            overflow: hidden;
        }
        
        .left-panel { 
            flex: 0.82;
            display: flex; 
            flex-direction: column; 
            gap: 16px; 
            min-width: 0;
            height: 100%;
            overflow: hidden;
        }
        
        .top-row { 
            display: flex; 
            gap: 16px; 
            flex: 2.5;
            min-height: 0;
            overflow: hidden;
        }
        
        .info-card {
            flex: 1;
            background: white;
            border-radius: 24px;
            padding: 12px 10px;
            border: 1px solid #e0e6ed;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            overflow-y: auto;
        }
        
        .brand-logo {
            width: 100%;
            text-align: center;
            margin-bottom: 8px;
        }

        .brand-logo img {
            max-width: 140px;
            width: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
        }
        .live-time {
            font-size: 2.9rem;
            font-weight: 700;
            font-family: monospace;
            letter-spacing: 3px;
            color: #042d5c;
        }
        .day-date { font-size: 1rem; color: #6c757d; }
        .contact-info { margin-top: 18px; font-size: 0.9rem; color: #6c757d; line-height: 1.3; }
        
        .brochure-area {
            flex: 2.2;
            background: white;
            border-radius: 24px;
            overflow: hidden;
            position: relative;
            border: 1px solid #e0e6ed;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .brochure-slider {
            height: 100%;
            aspect-ratio: 4 / 5;
            position: relative;
            max-width: 100%;
        }
        .brochure-slider img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: white;
            position: absolute;
            top: 0;
            left: 0;
            opacity: 0;
            transition: opacity 0.9s ease-in-out;
        }
        .brochure-slider img.active-brochure { opacity: 1; z-index: 2; }
        
        .event-area {
            flex: 1;
            background: white;
            border-radius: 24px;
            overflow: hidden;
            padding: 8px;
            border: 1px solid #e0e6ed;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }
        .event-carousel { 
            width: 100%; 
            height: 100%; 
            overflow: hidden; 
            border-radius: 20px;
            position: relative;
        }
        .event-track { 
            display: flex; 
            width: 100%; 
            height: 100%; 
            transition: transform 0.7s cubic-bezier(0.2, 0.9, 0.4, 1.1);
        }
        .event-slide { 
            flex: 0 0 33.333%; 
            height: 100%; 
            padding: 0 4px;
            box-sizing: border-box;
        }
        .event-slide img { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
            background: #f8f9fa; 
            border-radius: 16px;
        }
        
        .right-panel { 
            flex: 1.18; 
            display: flex; 
            flex-direction: column; 
            gap: 16px; 
            min-width: 0;
            height: 100%;
            overflow: hidden;
        }
        
        .timetable-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 16px;
            overflow-y: auto;
            padding-right: 4px;
            min-height: 0;
        }
        
        .timetable-container::-webkit-scrollbar {
            width: 6px;
        }
        .timetable-container::-webkit-scrollbar-track {
            background: #e0e0e0;
            border-radius: 10px;
        }
        .timetable-container::-webkit-scrollbar-thumb {
            background: #042d5c;
            border-radius: 10px;
        }
        
        .timetable-card {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            border: 1px solid #e0e6ed;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            max-height: 40vh;
        }
        
        .timetable-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 14px;
            background: #f8f9fa;
            border-bottom: 3px solid;
            flex-shrink: 0;
        }
        
        .timetable-header.bms-header { 
            border-bottom-color: #042d5c; 
            background: linear-gradient(135deg, #042d5c08, #f8f9fa);
        }
        .timetable-header.bms-header h3 { color: #042d5c; }
        .timetable-card.bms-card .timetable-body .schedule-table th { 
            background: #042d5c; 
            color: white;
            text-align: center;
        }
        
        .timetable-header.cgs-header { 
            border-bottom-color: #dc3545; 
            background: linear-gradient(135deg, #dc354508, #f8f9fa);
        }
        .timetable-header.cgs-header h3 { color: #dc3545; }
        .timetable-card.cgs-card .timetable-body .schedule-table th { 
            background: #dc3545; 
            color: white;
            text-align: center;
        }
        
        .timetable-header h3 {
            font-weight: 700;
            margin: 0;
            font-size: 0.85rem;
        }
        
        .count-badge {
            background: #e9ecef;
            border-radius: 30px;
            padding: 2px 8px;
            font-size: 0.55rem;
            color: #495057;
            font-weight: 600;
        }
        
        .timetable-body {
            padding: 8px;
            overflow-y: auto;
            flex: 1;
            min-height: 0;
        }
        
        .timetable-body::-webkit-scrollbar {
            width: 4px;
        }
        .timetable-body::-webkit-scrollbar-track {
            background: #e9ecef;
            border-radius: 10px;
        }
        .timetable-body::-webkit-scrollbar-thumb {
            background: #adb5bd;
            border-radius: 10px;
        }
        
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
            table-layout: fixed;
        }
        
        .schedule-table th {
            padding: 4px 3px;
            text-align: center;
            font-weight: 500;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .schedule-table th:nth-child(1) { width: 12%; }
        .schedule-table th:nth-child(2) { width: 23%; }
        .schedule-table th:nth-child(3) { width: 10%; }
        .schedule-table th:nth-child(4) { width: 29%; }
        .schedule-table th:nth-child(5) { width: 16%; }
        
        .schedule-table td {
            padding: 4px 3px;
            border-bottom: 1px solid #e9ecef;
            color: #2c3e50;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
        }
        
        .schedule-table td:first-child {
            text-align: left;
            font-weight: 500;
        }
        
        .schedule-table td:nth-child(2) {
            text-align: left;
        }
        
        .schedule-table td:nth-child(4) {
            text-align: left;
        }
        
        .schedule-table tr:hover { background: #f8f9fa; }
        
        .hall-tag {
            background: #e9ecef;
            border-radius: 20px;
            padding: 2px 5px;
            font-size: 0.8rem;
            display: block;
            font-weight: 600;
            color: #024f6d;
            width: 100%;
            text-align: center;
            box-sizing: border-box;
  
        }
        
        .type-tag {
            border-radius: 20px;
            padding: 2px 8px;
            font-size: 0.65rem;
            display: inline-block;
            font-weight: 600;
            margin-left: 6px;
            width: auto;
            min-width: 60px;
            text-align: center;
        }
        
        .exam-tag { background: #dc3545; color: white; }
        .review-tag { background: #92023e; color: white; }
        .presentation-tag { background: #083970; color: white; }
        .parents-tag { background: #6f42c1; color: white; }
        .meeting-tag { background: #fd7e14; color: white; }
        .event-tag-default { background: #8b597b; color: #ffffff; }
        .move-tag { background: #116149; color: white; }
        .workshop-tag { background: #752f50; color: white; }
        
        .refresh-timer {
            font-size: 0.55rem;
            text-align: center;
            padding: 5px;
            color: #6c757d;
            background: white;
            border-radius: 20px;
            border: 1px solid #e0e6ed;
            flex-shrink: 0;
        }
        
        .refresh-timerx {
            font-size: 0.7rem;
            text-align: center;
            padding: 5px;
            color: #6c757d;
            background: white;
            border-radius: 20px;
            border: 1px solid #e0e6ed;
            flex-shrink: 0;
        }

        .loading-state, .empty-state, .error-state {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            flex-direction: column;
            gap: 8px;
            text-align: center;
            padding: 12px;
        }
        
        .empty-state { color: #6c757d; }
        .error-state { color: #dc3545; }
        
        .status-dot {
            display: inline-block;
            width: 6px;
            height: 6px;
            background: #28a745;
            border-radius: 50%;
            margin-right: 5px;
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 0.3; transform: scale(0.8);}
            100% { opacity: 1; transform: scale(1.2);}
        }
        
        @media (max-width: 1200px) {
            .live-time { font-size: 2rem; }
            .schedule-table { font-size: 0.55rem; }
            .timetable-header h3 { font-size: 0.75rem; }
            .timetable-header { padding: 8px 12px; }
            .timetable-body { padding: 6px; }
            .schedule-table th, .schedule-table td { padding: 3px 2px; }
            .brand-logo img { max-width: 100px; }
            .type-tag { font-size: 0.55rem; padding: 1px 5px; margin-left: 4px; }
        }
        
        @media (max-width: 992px) {
            .live-time { font-size: 1.6rem; }
            .brand-logo img { max-width: 80px; }
        }
        
        @media (max-width: 768px) {
            .dashboard { flex-direction: column; }
            .top-row { flex-direction: column; }
            .timetable-card { max-height: 35vh; }
        }
        
        .brochure-slider img {
            object-fit: contain;
        }
        
        .module-text {
            font-size: 0.85rem;
            line-height: 1.3;
        }
        
        .programme-name {
            font-size: .0.85rem;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="dashboard">
    <div class="left-panel">
        <div class="top-row">
            <div class="info-card">
                <div class="brand-logo">
                    <img src="assets/BMS-Logo.png"
                        alt="BMS Logo"
                        onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 200 80\'%3E%3Crect width=\'200\' height=\'80\' fill=\'%23042d5c\'/%3E%3Ctext x=\'100\' y=\'48\' fill=\'white\' font-size=\'20\' text-anchor=\'middle\' font-weight=\'bold\'%3EBMS%3C/text%3E%3C/svg%3E'">
                </div>
                <div class="clock-wrapper"><br>
                    <div class="live-time" id="currentTime">--:--</div> 
                    <div class="day-date" id="fullDate">Loading...</div>
                </div>
                <div class="contact-info">
                    www.bms.ac.lk<br>
                    +94 112 504 757<br>
                    +94 112 360 978<br>
                    info@bms.ac.lk
                </div>
            </div>
            <div class="brochure-area">
                <div class="brochure-slider" id="brochureSlider">
                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 800 600'%3E%3Crect width='800' height='600' fill='%23f8f9fa'/%3E%3Ctext x='400' y='300' fill='%23042d5c' font-size='22' text-anchor='middle'%3EBROCHURE FOLDER%3C/text%3E%3Ctext x='400' y='340' fill='%236c757d' font-size='14' text-anchor='middle'%3Eupload images to /bmstv/images/brochures/%3C/text%3E%3C/svg%3E" class="active-brochure">
                </div>
            </div>
        </div>
        <div class="event-area">
            <div class="event-carousel">
                <div class="event-track" id="eventTrack">
                    <div class="event-slide">
                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 400 250'%3E%3Crect width='400' height='250' fill='%23f8f9fa'/%3E%3Ctext x='200' y='130' fill='%23dc3545' font-size='18' text-anchor='middle'%3EEVENT 1%3C/text%3E%3C/svg%3E">
                    </div>
                    
                </div>
            </div>
        </div>

        <div class="refresh-timerx">
            BMS is entering its 27 years of success in providing high quality education in association with the best of the British universities, while incorporating the flexibility of the module credit system leading to a British degree. BMS is a degree-awarding institute in terms of the Universities Act No. 16 of 1978 since January 2021. BMS has an unparalleled reputation for quality across all its services and has received commendations from students, parents, and partner institutions.
        </div>
    </div>

    <div class="right-panel">
        <div class="timetable-container" id="timetableContainer">
            <div class="loading-state">
                <div class="spinner-border text-primary" role="status"></div>
                <span>Loading timetable...</span>
            </div>
        </div>
        <div class="refresh-timer">
            <!-- Live from class_reservations | Auto-refresh 60s | Sorted by Classroom Branch -->
            Designed By: Web and System Administration- BMS
        </div>
    </div>
</div>

<script>
    // Classroom name mapping with floor information
    const hallNameMapping = {
        "Julie": "Julie (2nd Floor)",
        "Paleela": "Paleela (2nd Floor)",
        "Wijayaratna": "Wijayaratna (2nd Floor)",
        "Harvard": "Harvard (3rd Floor)",
        "Bluebell": "Bluebell (4th Floor)",
        "Digital Hub": "Digital Hub (4th Floor)",
        "Jean": "Jean (5th Floor)",
        "302": "302 (3rd Floor)",
        "Auditorium": "Auditorium (1st Floor)",
        "204": "204 (2nd Floor)",
        "Al-Qasim": "Al-Qasim (3rd Floor)",
        "Barton": "Barton (2nd Floor)",
        "Paul": "Paul (5th Floor)",
        "Mandela": "Mandela (5th Floor)",
        "Osman": "Osman (2nd Floor)"
    };

    // Function to get formatted hall name with floor
    function getFormattedHallName(hallName) {
        if (!hallName) return '';
        // Check if the hall name exists in mapping (case-insensitive)
        for (const [key, value] of Object.entries(hallNameMapping)) {
            if (hallName.toLowerCase() === key.toLowerCase()) {
                return value;
            }
        }
        // Return original name if not found in mapping
        return hallName;
    }

    // Programme short names mapping
    const programmeShortNames = {
        "International Foundation Diploma (Business)": "IFD (BUSINESS)",
        "International Foundation Diploma (Applied Science)": "IFD (APPLIED SCIENCE)",
        "BTEC Higher National Diploma in Business": "BTecHNDB",
        "Higher Diploma in Biomedical Science": "HDBS",
        "Higher Diploma in Biotechnology": "HDBT",
        "Higher Diploma in Food Science and Nutrition": "HDFSN",
        "Executive Certificate in Management": "ECM",
        "Graduate Diploma in Management (Level 6)": "GDM",
        "Bachelor of Business Management (Hons)": "BBM",
        "Higher Diploma in Medical Biotechnology": "HDMB",
        "BSc (Hons) in Software Engineering": "BSE",
        "BSc (Hons) Global Business Management": "BGBM",
        "BSc (Hons) Global Business Management Marketing": "BGBMM",
        "BSc (Hons) Global Business Management Human Resources Management": "BGBMHRM",
        "BSc (Hons) Accounting & Finance": "BAF",
        "BSc (Hons) International Tourism, Hospitality & Events": "BITHE",
        "BSc (Hons) Biomedical Science": "BBS",
        "BSc (Hons) Biotechnology": "BB",
        "BSc (Hons) Medical Biotechnology": "BMB",
        "BSc (Hons) Food Science & Nutrition": "BFSN",
        "MSc Cancer & Molecular Diagnostics": "MMD",
        "Master of Business Adminstration": "MBA",
        "MSc Business Intelligence and Analytics": "MBIA",
        "MSc in Digital Marketing": "MDM",
        "MBA in Digital Transformation": "MDT",
        "MSc in Management": "MM"
    };

    function getShortProgrammeName(fullName) {
        if (!fullName) return '';
        if (programmeShortNames[fullName]) {
            return programmeShortNames[fullName];
        }
        for (const [key, value] of Object.entries(programmeShortNames)) {
            if (fullName.toLowerCase().includes(key.toLowerCase())) {
                return value;
            }
        }
        return fullName.length > 25 ? fullName.substring(0, 22) + '...' : fullName;
    }
    
    // Convert 24-hour time to 12-hour format without AM/PM text
    function formatTo12HourNoAMPM(time24) {
        if (!time24) return '';
        let [hours, minutes] = time24.split(':');
        hours = parseInt(hours);
        let hours12 = hours % 12;
        hours12 = hours12 ? hours12 : 12;
        return `${hours12.toString().padStart(2, '0')}:${minutes}`;
    }
    
    // Get CSS class for type badge
    function getTypeTagClass(type) {
        if (!type) return 'event-tag-default';
        const typeLower = type.toLowerCase();
        if (typeLower.includes('exam')) return 'exam-tag';
        if (typeLower.includes('review')) return 'review-tag';
        if (typeLower.includes('presentation')) return 'presentation-tag';
        if (typeLower.includes('parents')) return 'parents-tag';
        if (typeLower.includes('meeting')) return 'meeting-tag';
        if (typeLower.includes('move')) return 'move-tag';
        if (typeLower.includes('workshop')) return 'workshop-tag';
        if (typeLower.includes('event')) return 'event-tag-default';
        return 'event-tag-default';
    }
    
    // Get programme display with type badge (excludes Lecture and Guest Lecture)
    function getProgrammeDisplay(programmeName, classType) {
        const shortName = getShortProgrammeName(programmeName);
        let html = `<div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                        <span class="programme-name">${escapeHtml(shortName)}</span>`;
        
        if (
            classType &&
            classType.trim() !== '' &&
            classType.toLowerCase() !== 'lecture' &&
            classType.toLowerCase() !== 'guest lecture'
        ) {
            const tagClass = getTypeTagClass(classType);
            html += `<span class="type-tag ${tagClass}">${escapeHtml(classType)}</span>`;
        }
        
        html += `</div>`;
        return html;
    }

    // Real time clock
    function updateDateTimeUI() {
        const now = new Date();
        let hours = now.getHours();
        const minutes = now.getMinutes().toString().padStart(2, '0');
        let hours12 = hours % 12;
        hours12 = hours12 ? hours12 : 12;
        document.getElementById('currentTime').innerText = `${hours12.toString().padStart(2, '0')}:${minutes}`;
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('fullDate').innerText = now.toLocaleDateString(undefined, options);
    }
    updateDateTimeUI();
    setInterval(updateDateTimeUI, 1000);

    // API Endpoints
    const BROCHURE_API = 'bmstv/list_brochures.php';
    const EVENTS_API = 'bmstv/list_events.php';
    const TIMETABLE_API = 'bmstv/timetable_schedule.php';

    let brochureImages = [], eventImages = [];
    let currentBrochureIdx = 0;
    let brochureInterval, eventInterval;

    async function fetchImagesFromFolder(apiUrl, type) {
        try {
            const response = await fetch(apiUrl);
            if (!response.ok) throw new Error('HTTP ' + response.status);
            const data = await response.json();
            if (data.images && data.images.length > 0) {
                return data.images;
            }
            return [];
        } catch (err) {
            console.error(`Failed to load ${type}:`, err);
            return [];
        }
    }

    async function initBrochureShow() {
        const container = document.getElementById('brochureSlider');
        let images = await fetchImagesFromFolder(BROCHURE_API, 'Brochure');
        
        if (images.length === 0) {
            images = ["data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 800 600'%3E%3Crect width='800' height='600' fill='%23f8f9fa'/%3E%3Ctext x='400' y='300' fill='%23042d5c' font-size='20' text-anchor='middle'%3ENo Brochures Found%3C/text%3E%3Ctext x='400' y='340' fill='%236c757d' font-size='14' text-anchor='middle'%3EAdd images to /bmstv/images/brochures/%3C/text%3E%3C/svg%3E"];
        }
        
        brochureImages = images;
        container.innerHTML = '';
        
        images.forEach((src, idx) => {
            const img = document.createElement('img');
            img.src = src;
            img.alt = `Brochure ${idx+1}`;
            if (idx === 0) img.classList.add('active-brochure');
            container.appendChild(img);
        });
        
        if (brochureImages.length > 1) {
            if (brochureInterval) clearInterval(brochureInterval);
            brochureInterval = setInterval(() => {
                const allImgs = document.querySelectorAll('#brochureSlider img');
                if (allImgs.length === 0) return;
                allImgs[currentBrochureIdx]?.classList.remove('active-brochure');
                currentBrochureIdx = (currentBrochureIdx + 1) % allImgs.length;
                allImgs[currentBrochureIdx]?.classList.add('active-brochure');
            }, 6000);
        }
    }

    async function initEventShow() {
        const track = document.getElementById('eventTrack');
        let events = await fetchImagesFromFolder(EVENTS_API, 'Event');
        
        if (events.length === 0) {
            events = [];
            for (let i = 1; i <= 6; i++) {
                events.push(`data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 400 250'%3E%3Crect width='400' height='250' fill='%23f8f9fa'/%3E%3Ctext x='200' y='130' fill='%23dc3545' font-size='18' text-anchor='middle'%3EEVENT ${i}%3C/text%3E%3C/svg%3E`);
            }
        }
        
        eventImages = events;
        track.innerHTML = '';
        
        events.forEach((url, idx) => {
            const slideDiv = document.createElement('div');
            slideDiv.className = 'event-slide';
            const img = document.createElement('img');
            img.src = url;
            img.alt = `Event ${idx+1}`;
            slideDiv.appendChild(img);
            track.appendChild(slideDiv);
        });
        
        // Continuous looping slider - shows 3 images at a time, loops seamlessly
        if (eventImages.length > 3) {
            if (eventInterval) clearInterval(eventInterval);
            
            const totalSlides = eventImages.length;
            const slidesToShow = 3;
            
            const slides = document.querySelectorAll('.event-slide');
            if (slides.length >= slidesToShow) {
                for (let i = 0; i < slidesToShow; i++) {
                    const clone = slides[i].cloneNode(true);
                    track.appendChild(clone);
                }
            }
            
            let currentPosition = 0;
            const slideWidthPercent = (100 / slidesToShow);
            
            eventInterval = setInterval(() => {
                currentPosition++;
                const translatePercent = currentPosition * slideWidthPercent;
                track.style.transform = `translateX(-${translatePercent}%)`;
                track.style.transition = 'transform 0.7s cubic-bezier(0.2, 0.9, 0.4, 1.1)';
                
                if (currentPosition >= totalSlides) {
                    setTimeout(() => {
                        track.style.transition = 'none';
                        currentPosition = 0;
                        track.style.transform = `translateX(0%)`;
                        track.offsetHeight;
                        track.style.transition = 'transform 0.7s cubic-bezier(0.2, 0.9, 0.4, 1.1)';
                    }, 700);
                }
            }, 4000);
        }
    }

    setInterval(() => { initBrochureShow(); initEventShow(); }, 300000);

    async function loadTimetableFromDB() {
        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000);
            const response = await fetch(TIMETABLE_API, { signal: controller.signal });
            clearTimeout(timeoutId);
            if (!response.ok) throw new Error('HTTP ' + response.status);
            const data = await response.json();
            return data;
        } catch (err) {
            console.error("Timetable API error:", err);
            return { error: true, message: err.message, data: { bms: [], cgs: [] } };
        }
    }

    function buildTimetableCard(title, branchClass, headerClass, sessions) {
        if (!sessions || sessions.length === 0) {
            return `<div class="timetable-card ${branchClass}">
                        <div class="timetable-header ${headerClass}">
                            <h3>${title}</h3>
                            <!-- <span class="count-badge">0 classes</span> -->
                        </div>
                        <div class="empty-state">
                            <p>No ${title} lectures scheduled for today</p>
                        </div>
                    </div>`;
        }
        
        let html = `<div class="timetable-card ${branchClass}">
                        <div class="timetable-header ${headerClass}">
                            <h3>${title}</h3>
                            <!--<span class="count-badge">${sessions.length} classes</span>-->
                        </div>
                        <div class="timetable-body">
                            <table class="schedule-table">
                                <thead>
                                    <tr><th>Time</th><th>Programme</th><th>Batch</th><th>Module</th><th>Hall</th> </thead>
                                <tbody>`;
        
        sessions.forEach(s => {
            // Format time to 12-hour without AM/PM
            let timeSlot = s.time_slot;
            if (timeSlot && timeSlot.includes('-')) {
                const times = timeSlot.split('-');
                const start12 = formatTo12HourNoAMPM(times[0].trim());
                const end12 = formatTo12HourNoAMPM(times[1].trim());
                timeSlot = `${start12} - ${end12}`;
            } else {
                timeSlot = formatTo12HourNoAMPM(timeSlot);
            }
            
            const moduleContent = (s.module_display && s.module_display.trim() !== '') ? s.module_display : '—';
            const programmeDisplay = getProgrammeDisplay(s.programme_name, s.type);
            // Format hall name with floor information
            const formattedHallName = getFormattedHallName(s.hall_name);
            
            html += `<tr>
                        <td><strong>${escapeHtml(timeSlot)}</strong></td>
                        <td>${programmeDisplay}</td>
                        <td>${escapeHtml(s.batch_name)}</td>
                        <td><span class="module-text">${escapeHtml(moduleContent)}</span></td>
                        <td><span class="hall-tag">${escapeHtml(formattedHallName)}</span></td>
                      </tr>`;
        });
        
        html += `</tbody>
                            </table>
                        </div>
                    </div>`;
        return html;
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }

    async function renderTimetable() {
        const container = document.getElementById('timetableContainer');
        container.innerHTML = `<div class="loading-state"><div class="spinner-border text-primary" role="status"></div><span>Loading schedule...</span></div>`;
        
        const result = await loadTimetableFromDB();
        
        if (result.error) {
            container.innerHTML = `<div class="error-state">
                                        <p>${result.message}</p>
                                    </div>`;
            return;
        }
        
        const bmsClasses = result.data?.bms || [];
        const cgsClasses = result.data?.cgs || [];
        
        const bmsCard = buildTimetableCard('BMS', 'bms-card', 'bms-header', bmsClasses);
        const cgsCard = buildTimetableCard('CGS', 'cgs-card', 'cgs-header', cgsClasses);
        
        container.innerHTML = bmsCard + cgsCard;
    }
    
    // Initial load
    renderTimetable();
    setInterval(renderTimetable, 10800000); //timetable auto refresh time 1mx60mx3h
    
    initBrochureShow();
    initEventShow();
</script>
</body>
</html>