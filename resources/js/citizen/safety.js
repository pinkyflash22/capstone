// Safety Guide JavaScript

document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // ===== GUIDE NAVIGATION =====
    const guideLinks = document.querySelectorAll('.guide-link');
    const guideContents = document.querySelectorAll('.guide-content');
    
    guideLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Update active state in menu
            guideLinks.forEach(l => {
                l.classList.remove('bg-orange-50', 'text-orange-700', 'font-medium');
                l.classList.add('hover:bg-gray-50');
            });
            this.classList.remove('hover:bg-gray-50');
            this.classList.add('bg-orange-50', 'text-orange-700', 'font-medium');
            
            // Show selected guide
            const guideId = this.dataset.guide;
            guideContents.forEach(content => {
                content.classList.add('hidden');
            });
            document.getElementById(guideId + '-guide').classList.remove('hidden');
        });
    });

    // ===== DOWNLOAD FUNCTIONALITY =====
    const downloadButtons = document.querySelectorAll('.download-guide-btn');
    const modal = document.getElementById('download-modal');
    const closeModal = document.getElementById('close-modal');
    const cancelDownload = document.getElementById('cancel-download');
    const saveManual = document.getElementById('save-manual');
    const modalTitle = document.getElementById('modal-guide-title');
    const progressBar = document.getElementById('download-progress');
    
    let downloadTimer;
    
    // Guide content for downloads
    const guideContent = {
        fire: `FIRE SAFETY GUIDE

ESSENTIAL FIRE SAFETY TIPS:
1. Install smoke alarms on every level of your home
2. Test smoke alarms monthly and replace batteries yearly
3. Create and practice a home escape plan with two ways out of every room
4. Keep fire extinguishers in key areas (kitchen, garage, near exits)
5. Learn how to use a fire extinguisher using the PASS method:
   - PULL the pin
   - AIM at the base of the fire
   - SQUEEZE the handle
   - SWEEP from side to side
6. Check electrical cords for damage and don't overload outlets
7. Never leave cooking unattended
8. Keep matches and lighters away from children
9. Know your emergency number: 911
10. Designate a meeting spot outside your home

FIRE SAFETY STATISTICS:
• 356,000 home fires occur annually in the US
• 2,500+ deaths and 11,000+ injuries each year
• $7 billion in property damage annually
• Cooking is the leading cause of home fires (50%)
• Smoking materials are the leading cause of fire deaths

EMERGENCY CONTACTS:
• Fire Emergency: 911
• Non-Emergency Fire Dept: [Your Local Number]
• Poison Control: 1-800-222-1222

Remember: In case of fire, GET OUT, STAY OUT, and CALL for help. Never go back inside for anything.`,

        flood: `FLOOD PREPAREDNESS GUIDE

BEFORE A FLOOD:
1. Know your flood risk zone
2. Prepare emergency kit with documents in waterproof container
3. Plan evacuation routes to higher ground
4. Purchase flood insurance if available
5. Clear gutters and drains regularly
6. Sandbag doorways if time permits
7. Keep emergency radio for weather updates
8. Charge all electronic devices

DURING A FLOOD:
1. Move to higher ground immediately
2. Never walk or drive through floodwater (6 inches can knock you down)
3. Stay away from electrical equipment in wet areas
4. Avoid bridges over fast-moving water
5. Listen to emergency broadcasts
6. Turn off utilities if instructed
7. Evacuate immediately if told to do so

AFTER A FLOOD:
1. Wait for official all-clear before returning home
2. Avoid standing water - may be electrically charged
3. Check for structural damage before entering
4. Document damage for insurance claims
5. Clean and disinfect everything that got wet
6. Watch for mold and mildew
7. Throw away food that came in contact with floodwater

FLOOD SAFETY CHECKLIST:
□ Sandbags for doorways
□ Battery-powered pump
□ Waterproof document container
□ Emergency radio
□ Portable charger
□ First aid kit
□ 3-day supply of water and food
□ Flashlights and extra batteries
□ Rubber boots and gloves`,

        earthquake: `EARTHQUAKE SAFETY GUIDE

BEFORE AN EARTHQUAKE:
1. Secure heavy furniture to walls
2. Prepare 3-day emergency kit
3. Know gas and water shut-off valves
4. Identify safe spots in each room
5. Practice "Drop, Cover, and Hold On" drills
6. Create family emergency communication plan
7. Bolt bookcases and cabinets to wall studs
8. Install flexible gas lines to prevent leaks

DURING AN EARTHQUAKE:
• INDOORS: DROP to the ground, COVER under sturdy furniture, HOLD ON until shaking stops
• If no cover, crouch against interior wall and cover head/neck
• Stay away from windows, glass, and exterior walls
• Don't use elevators or doorways (old advice)
• If in bed, stay there and cover head with pillow
• If outdoors, move to open area away from buildings, trees, power lines
• If driving, pull over away from structures and stay in vehicle

AFTER AN EARTHQUAKE:
1. Check for injuries and provide first aid
2. Expect aftershocks - be prepared
3. Listen to emergency broadcasts
4. Use text messages for communication (less network congestion)
5. Check for gas leaks and electrical damage
6. Clean up spilled medications and hazardous materials
7. Open cabinets carefully (items may have shifted)
8. Stay out of damaged buildings

EMERGENCY KIT ITEMS:
□ Water (1 gallon/person/day for 3 days)
□ Non-perishable food and can opener
□ First aid kit and medications
□ Flashlight and extra batteries
□ Whistle to signal for help
□ Dust mask and sturdy shoes
□ Local maps and important documents
□ Cash and IDs
□ Phone charger and backup battery`,

        firstaid: `FIRST AID ESSENTIALS GUIDE

CPR (Cardiopulmonary Resuscitation):
• Check scene safety and responsiveness
• Call 911 or send someone to call
• 30 chest compressions (100-120 per minute, 2 inches deep)
• 2 rescue breaths (if trained)
• Continue until help arrives or person shows signs of life
• Hands-only CPR: 100-120 compressions per minute without breaths

CONTROLLING BLEEDING:
1. Apply direct pressure with clean cloth or sterile gauze
2. Elevate wound above heart if possible
3. Apply pressure bandage once bleeding slows
4. Do NOT remove objects embedded in wound
5. For severe bleeding, use tourniquet as last resort
6. Seek immediate medical attention

BURN TREATMENT:
• Cool with running cool (not cold) water for 10-15 minutes
• Cover with sterile non-stick gauze
• Do NOT apply ice, butter, oil, or creams
• Do NOT pop blisters
• Seek medical help for severe, large, or facial burns

CHOKING (Heimlich Maneuver):
• Stand behind person, wrap arms around waist
• Make fist above navel, grasp with other hand
• Thrust inward and upward
• Repeat until object is expelled or person becomes unconscious
• For pregnant/obese, give chest thrusts

FIRST AID KIT MUST-HAVES:
□ Adhesive bandages (assorted sizes)
□ Sterile gauze pads and medical tape
□ Antiseptic wipes and antibiotic cream
□ Pain relievers (ibuprofen, acetaminophen)
□ Antihistamines (for allergic reactions)
□ Emergency blanket
□ Disposable gloves
□ CPR mask
□ Scissors and tweezers
□ Instant ice packs
□ Burn cream and hydrocortisone
□ First aid manual

WHEN TO CALL 911:
• Unconscious or unresponsive
• Chest pain or difficulty breathing
• Severe bleeding that won't stop
• Choking
• Head or neck injury
• Severe allergic reaction
• Poisoning (also call Poison Control: 1-800-222-1222)`,

        gobag: `GO-BAG EMERGENCY CHECKLIST

ESSENTIAL ITEMS (3-DAY SUPPLY):
□ Water - 1 gallon per person per day
□ Non-perishable food (protein bars, canned goods)
□ Manual can opener
□ First aid kit and medications
□ Flashlight and extra batteries
□ Whistle to signal for help
□ Dust mask and sturdy shoes
□ Local maps and important documents (copies)
□ Cash ($100-200 in small bills)
□ Multi-tool or utility knife
□ Portable charger and cables
□ Emergency blanket
□ Personal hygiene items
□ Change of clothes
□ N95 masks and hand sanitizer
□ List of emergency contacts

ADDITIONAL ITEMS FOR FAMILIES:
□ Baby supplies (formula, diapers, wipes)
□ Pet supplies (food, water, leash, medications)
□ Entertainment (small games, books for children)
□ Comfort items (stuffed animal, blanket)

IMPORTANT DOCUMENTS (IN WATERPROOF CONTAINER):
□ IDs and passports (copies)
□ Insurance policies
□ Birth certificates
□ Marriage certificate
□ Medical records and prescriptions
□ Deeds and vehicle titles
□ Emergency contact list
□ Family photos (copies)

SPECIAL NEEDS:
□ Prescription medications (7-day supply)
□ Medical equipment and supplies
□ Glasses and contact lens solution
□ Hearing aids with extra batteries
□ Mobility aids (cane, walker, wheelchair)

GO-BAG MAINTENANCE:
• Store in easily accessible place
• Check every 6 months (when clocks change)
• Replace expired items and medications
• Update documents and cash
• Rotate food and water supplies
• Adjust for seasons (add warm clothes in winter)

PRO TIP: Keep a smaller version in your car and at work. Review and practice your emergency plan with family members twice a year.`
    };
    
    downloadButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const guideType = this.dataset.guide;
            let guideName = '';
            
            switch(guideType) {
                case 'fire': guideName = 'Fire Safety Guide'; break;
                case 'flood': guideName = 'Flood Preparedness Guide'; break;
                case 'earthquake': guideName = 'Earthquake Safety Guide'; break;
                case 'firstaid': guideName = 'First Aid Guide'; break;
                case 'gobag': guideName = 'Go-Bag Checklist Guide'; break;
            }
            
            modalTitle.textContent = guideName;
            modal.classList.remove('hidden');
            
            // Simulate download progress
            let progress = 0;
            progressBar.style.width = '0%';
            
            downloadTimer = setInterval(() => {
                progress += 10;
                progressBar.style.width = progress + '%';
                
                if (progress >= 100) {
                    clearInterval(downloadTimer);
                    // Trigger actual download after progress completes
                    triggerDownload(guideType, guideName);
                    
                    setTimeout(() => {
                        modal.classList.add('hidden');
                        progressBar.style.width = '0%';
                    }, 500);
                }
            }, 200);
        });
    });
    
    function triggerDownload(guideType, guideName) {
        const content = guideContent[guideType] || 'No content available';
        
        // Create and download text file
        const blob = new Blob([content], { type: 'text/plain' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${guideName.toLowerCase().replace(/\s+/g, '-')}.txt`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }
    
    // Close modal handlers
    function closeDownloadModal() {
        clearInterval(downloadTimer);
        modal.classList.add('hidden');
        progressBar.style.width = '0%';
    }
    
    if (closeModal) closeModal.addEventListener('click', closeDownloadModal);
    if (cancelDownload) cancelDownload.addEventListener('click', closeDownloadModal);
    
    if (saveManual) {
        saveManual.addEventListener('click', function() {
            const guideName = modalTitle.textContent;
            const guideType = guideName.toLowerCase().includes('fire') ? 'fire' :
                             guideName.toLowerCase().includes('flood') ? 'flood' :
                             guideName.toLowerCase().includes('earthquake') ? 'earthquake' :
                             guideName.toLowerCase().includes('first') ? 'firstaid' : 'gobag';
            triggerDownload(guideType, guideName);
        });
    }
    
    // Close modal when clicking outside
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeDownloadModal();
            }
        });
    }

    // ===== CHECKLIST FUNCTIONALITY =====
    const checkboxes = document.querySelectorAll('.safety-checkbox');
    const progressText = document.getElementById('checklist-progress');
    const resetBtn = document.getElementById('reset-checklist');
    const saveBtn = document.getElementById('save-checklist');
    
    function updateChecklistProgress() {
        const checked = document.querySelectorAll('.safety-checkbox:checked').length;
        const total = checkboxes.length;
        if (progressText) progressText.textContent = `${checked}/${total} completed`;
        
        // Save to localStorage
        localStorage.setItem('safety-checklist', JSON.stringify(
            Array.from(checkboxes).map(cb => cb.checked)
        ));
    }
    
    // Load saved checklist state
    const savedChecklist = localStorage.getItem('safety-checklist');
    if (savedChecklist) {
        const states = JSON.parse(savedChecklist);
        checkboxes.forEach((cb, index) => {
            if (states[index]) cb.checked = true;
        });
        updateChecklistProgress();
    }
    
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateChecklistProgress);
    });
    
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            checkboxes.forEach(cb => cb.checked = false);
            updateChecklistProgress();
        });
    }
    
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            alert('Checklist progress saved!');
        });
    }

    // ===== VIEW TOGGLE =====
    const viewToggles = document.querySelectorAll('.view-toggle');
    const resourcesGrid = document.getElementById('resources-grid');
    
    viewToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            viewToggles.forEach(t => {
                t.classList.remove('active', 'bg-orange-600', 'text-white');
                t.classList.add('bg-gray-200', 'text-gray-700');
            });
            this.classList.remove('bg-gray-200', 'text-gray-700');
            this.classList.add('bg-orange-600', 'text-white');
            
            const view = this.dataset.view;
            if (resourcesGrid) {
                if (view === 'list') {
                    resourcesGrid.classList.remove('grid-cols-1', 'md:grid-cols-3');
                    resourcesGrid.classList.add('grid-cols-1');
                } else {
                    resourcesGrid.classList.add('grid-cols-1', 'md:grid-cols-3');
                    resourcesGrid.classList.remove('grid-cols-1');
                }
            }
        });
    });

    // ===== QUIZ FUNCTIONALITY =====
    const quizOptions = document.querySelectorAll('input[name="quiz"]');
    const checkAnswerBtn = document.getElementById('check-answer');
    const nextBtn = document.getElementById('next-question');
    const quizFeedback = document.getElementById('quiz-feedback');
    const quizProgress = document.getElementById('quiz-progress');
    const quizProgressBar = document.getElementById('quiz-progress-bar');
    
    let currentQuestion = 1;
    const totalQuestions = 5;
    let score = 0;
    
    const quizQuestions = [
        {
            question: 'What is the correct way to use a fire extinguisher?',
            options: ['PASS: Pull, Aim, Squeeze, Sweep', 'Spray and Run', 'Point and Shoot', 'Pour water on fire'],
            answer: 'A'
        },
        {
            question: 'What should you do if your clothes catch fire?',
            options: ['Run fast', 'Stop, Drop, and Roll', 'Jump in water', 'Wrap in blanket'],
            answer: 'B'
        },
        {
            question: 'Where should smoke alarms be installed?',
            options: ['Only in kitchen', 'Basement only', 'Every level of home', 'Outside only'],
            answer: 'C'
        },
        {
            question: 'How often should you test smoke alarms?',
            options: ['Once a year', 'Every 5 years', 'Monthly', 'Never'],
            answer: 'C'
        },
        {
            question: 'What is the first step in CPR?',
            options: ['Give breaths', 'Check responsiveness', 'Start compressions', 'Call 911'],
            answer: 'B'
        }
    ];
    
    if (checkAnswerBtn) {
        checkAnswerBtn.addEventListener('click', function() {
            const selected = document.querySelector('input[name="quiz"]:checked');
            if (!selected) {
                alert('Please select an answer');
                return;
            }
            
            const isCorrect = selected.value === quizQuestions[currentQuestion - 1].answer;
            quizFeedback.classList.remove('hidden');
            
            if (isCorrect) {
                quizFeedback.innerHTML = '<span class="text-green-300">✓ Correct! Good job!</span>';
                score++;
            } else {
                quizFeedback.innerHTML = `<span class="text-red-300">✗ Incorrect. The correct answer is: ${quizQuestions[currentQuestion - 1].options[0]}</span>`;
            }
            
            checkAnswerBtn.disabled = true;
            quizOptions.forEach(opt => opt.disabled = true);
            if (nextBtn) nextBtn.disabled = false;
        });
    }
    
    if (nextBtn) {
        nextBtn.addEventListener('click', function() {
            if (currentQuestion < totalQuestions) {
                currentQuestion++;
                const progress = ((currentQuestion - 1) / totalQuestions) * 100;
                if (quizProgress) quizProgress.textContent = Math.round(progress) + '%';
                if (quizProgressBar) quizProgressBar.style.width = progress + '%';
                
                // Update question
                const questionEl = document.querySelector('#quiz-options')?.previousElementSibling;
                if (questionEl) {
                    questionEl.textContent = quizQuestions[currentQuestion - 1].question;
                }
                
                // Update options
                const optionsContainer = document.getElementById('quiz-options');
                if (optionsContainer) {
                    optionsContainer.innerHTML = '';
                    quizQuestions[currentQuestion - 1].options.forEach((opt, index) => {
                        const letter = String.fromCharCode(65 + index); // A, B, C, D
                        optionsContainer.innerHTML += `
                            <label class="quiz-option block bg-white bg-opacity-10 rounded-lg p-3 hover:bg-opacity-20 cursor-pointer">
                                <input type="radio" name="quiz" class="mr-2" value="${letter}"> ${opt}
                            </label>
                        `;
                    });
                }
                
                // Reset for next question
                document.querySelectorAll('input[name="quiz"]').forEach(opt => {
                    opt.checked = false;
                    opt.disabled = false;
                });
                checkAnswerBtn.disabled = false;
                nextBtn.disabled = true;
                quizFeedback.classList.add('hidden');
            } else {
                // Show final score
                alert(`Quiz complete! Your score: ${score}/${totalQuestions}`);
                // Reset quiz
                currentQuestion = 1;
                score = 0;
                if (quizProgress) quizProgress.textContent = '0%';
                if (quizProgressBar) quizProgressBar.style.width = '0%';
                nextBtn.disabled = true;
                quizFeedback.classList.add('hidden');
            }
        });
    }

    // ===== EMERGENCY DIAL BUTTONS =====
    const emergencyButtons = document.querySelectorAll('.emergency-dial');
    emergencyButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const number = this.dataset.number;
            if (confirm(`Call ${number}?`)) {
                window.location.href = `tel:${number}`;
            }
        });
    });

    // ===== KIT BUILDER MODAL =====
    const kitBuilderBtn = document.getElementById('open-kit-builder');
    const kitModal = document.getElementById('kit-builder-modal');
    const closeKitBuilder = document.getElementById('close-kit-builder');
    const kitItems = document.querySelectorAll('.kit-item');
    const kitProgressBar = document.getElementById('kit-progress-bar');
    const kitProgressText = document.getElementById('kit-progress-text');
    const generateKitList = document.getElementById('generate-kit-list');
    const printKitList = document.getElementById('print-kit-list');
    
    if (kitBuilderBtn && kitModal) {
        kitBuilderBtn.addEventListener('click', function() {
            kitModal.classList.remove('hidden');
        });
    }
    
    if (closeKitBuilder && kitModal) {
        closeKitBuilder.addEventListener('click', function() {
            kitModal.classList.add('hidden');
        });
    }
    
    if (kitModal) {
        kitModal.addEventListener('click', function(e) {
            if (e.target === kitModal) {
                kitModal.classList.add('hidden');
            }
        });
    }
    
    function updateKitProgress() {
        const checked = document.querySelectorAll('.kit-item:checked').length;
        const total = document.querySelectorAll('.kit-item').length;
        const percentage = (checked / total) * 100;
        if (kitProgressBar) kitProgressBar.style.width = percentage + '%';
        if (kitProgressText) kitProgressText.textContent = `${checked} of ${total} items selected`;
    }
    
    kitItems.forEach(item => {
        item.addEventListener('change', updateKitProgress);
    });
    
    if (generateKitList) {
        generateKitList.addEventListener('click', function() {
            const missingItems = [];
            document.querySelectorAll('.kit-item').forEach(item => {
                if (!item.checked) {
                    const label = item.closest('label')?.innerText.trim() || 'Item';
                    missingItems.push(label);
                }
            });
            
            if (missingItems.length > 0) {
                alert('Items you still need:\n- ' + missingItems.join('\n- '));
            } else {
                alert('Congratulations! You have all essential items!');
            }
        });
    }
    
    if (printKitList) {
        printKitList.addEventListener('click', function() {
            window.print();
        });
    }

    // ===== EXPAND MAP =====
    const expandMap = document.getElementById('expand-map');
    if (expandMap) {
        expandMap.addEventListener('click', function(e) {
            e.preventDefault();
            alert('Full map view would open here with interactive features');
        });
    }

    // ===== START TRAINING BUTTONS =====
    const trainingBtns = document.querySelectorAll('.start-training');
    trainingBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const training = this.dataset.training;
            alert(`Starting ${training} training module... This would open an interactive tutorial.`);
        });
    });

    // ===== RESOURCE LINKS =====
    const resourceLinks = document.querySelectorAll('.resource-link');
    const resourcePanel = document.getElementById('resource-panel');
    const closeResource = document.getElementById('close-resource');
    const resourceTitle = document.getElementById('resource-title');
    const resourceContent = document.getElementById('resource-content');
    
    const resourceData = {
        'emergency-contacts': {
            title: 'Emergency Contacts',
            content: `
                <div class="space-y-3">
                    <div class="bg-red-50 p-4 rounded-lg">
                        <h4 class="font-bold text-red-800">🚨 NATIONAL EMERGENCY</h4>
                        <p class="text-2xl font-bold text-red-600">911</p>
                        <p class="text-xs text-gray-600">Available 24/7 for all emergencies</p>
                    </div>
                    
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h4 class="font-bold text-blue-800">🚒 FIRE DEPARTMENT</h4>
                        <p class="text-lg font-semibold">(555) 123-4567</p>
                        <p class="text-xs text-gray-600">Non-emergency line - For permits and inspections</p>
                    </div>
                    
                    <div class="bg-green-50 p-4 rounded-lg">
                        <h4 class="font-bold text-green-800">🚑 AMBULANCE</h4>
                        <p class="text-lg font-semibold">(555) 234-5678</p>
                        <p class="text-xs text-gray-600">Medical emergencies - Available 24/7</p>
                    </div>
                    
                    <div class="bg-yellow-50 p-4 rounded-lg">
                        <h4 class="font-bold text-yellow-800">👮 POLICE</h4>
                        <p class="text-lg font-semibold">(555) 345-6789</p>
                        <p class="text-xs text-gray-600">Non-emergency line - For reports and inquiries</p>
                    </div>
                    
                    <div class="bg-purple-50 p-4 rounded-lg">
                        <h4 class="font-bold text-purple-800">⚠️ POISON CONTROL</h4>
                        <p class="text-lg font-semibold">1-800-222-1222</p>
                        <p class="text-xs text-gray-600">24/7 poison help line - Free and confidential</p>
                    </div>
                    
                    <div class="bg-gray-100 p-4 rounded-lg">
                        <h4 class="font-bold text-gray-800">🏥 LOCAL HOSPITAL</h4>
                        <p class="text-lg font-semibold">(555) 456-7890</p>
                        <p class="text-xs text-gray-600">City General Hospital - Emergency Room 24/7</p>
                        <p class="text-xs text-gray-600">123 Medical Drive, Downtown</p>
                    </div>
                </div>
            `
        },
        'evacuation-centers': {
            title: 'Evacuation Centers',
            content: `
                <div class="space-y-3">
                    <div class="border-l-4 border-orange-500 pl-4 py-2">
                        <h4 class="font-bold">🏫 NORTH DISTRICT</h4>
                        <p class="text-sm">North High School Gymnasium</p>
                        <p class="text-xs text-gray-600">123 Education Ave, North District</p>
                        <p class="text-xs text-green-600">✅ Open 24/7 · Capacity: 500</p>
                        <p class="text-xs text-gray-500">Contact: (555) 111-2222</p>
                    </div>
                    
                    <div class="border-l-4 border-orange-500 pl-4 py-2">
                        <h4 class="font-bold">🏛️ CENTRAL DISTRICT</h4>
                        <p class="text-sm">City Convention Center</p>
                        <p class="text-xs text-gray-600">456 Main Street, Downtown</p>
                        <p class="text-xs text-green-600">✅ Open 24/7 · Capacity: 1200</p>
                        <p class="text-xs text-gray-500">Contact: (555) 333-4444</p>
                    </div>
                    
                    <div class="border-l-4 border-orange-500 pl-4 py-2">
                        <h4 class="font-bold">⛪ SOUTH DISTRICT</h4>
                        <p class="text-sm">St. Mary's Community Hall</p>
                        <p class="text-xs text-gray-600">789 South Blvd, Southside</p>
                        <p class="text-xs text-green-600">✅ Open 24/7 · Capacity: 300</p>
                        <p class="text-xs text-gray-500">Contact: (555) 555-6666</p>
                    </div>
                    
                    <div class="border-l-4 border-orange-500 pl-4 py-2">
                        <h4 class="font-bold">🏟️ WEST DISTRICT</h4>
                        <p class="text-sm">Westside Sports Complex</p>
                        <p class="text-xs text-gray-600">321 Sports Way, West District</p>
                        <p class="text-xs text-green-600">✅ Open 24/7 · Capacity: 800</p>
                        <p class="text-xs text-gray-500">Contact: (555) 777-8888</p>
                    </div>
                    
                    <div class="bg-blue-50 p-3 rounded-lg mt-4">
                        <p class="text-sm text-blue-800"><i class="fa-solid fa-bus mr-2"></i> Free transportation to evacuation centers available. Call (555) 567-8900 for pickup.</p>
                    </div>
                </div>
            `
        },
        'safety-videos': {
            title: 'Safety Training Videos',
            content: `
                <div class="space-y-4">
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <h4 class="font-bold mb-2">🔥 Fire Extinguisher Training</h4>
                        <p class="text-sm text-gray-600 mb-2">Learn the PASS method: Pull, Aim, Squeeze, Sweep</p>
                        <div class="bg-gray-200 h-32 rounded flex items-center justify-center cursor-pointer hover:bg-gray-300 transition-colors">
                            <i class="fa-solid fa-play text-4xl text-orange-600"></i>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <h4 class="font-bold mb-2">🫀 CPR & First Aid</h4>
                        <p class="text-sm text-gray-600 mb-2">Hands-only CPR and basic first aid techniques</p>
                        <div class="bg-gray-200 h-32 rounded flex items-center justify-center cursor-pointer hover:bg-gray-300 transition-colors">
                            <i class="fa-solid fa-play text-4xl text-orange-600"></i>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <h4 class="font-bold mb-2">🏃 Emergency Evacuation</h4>
                        <p class="text-sm text-gray-600 mb-2">How to safely evacuate during emergencies</p>
                        <div class="bg-gray-200 h-32 rounded flex items-center justify-center cursor-pointer hover:bg-gray-300 transition-colors">
                            <i class="fa-solid fa-play text-4xl text-orange-600"></i>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <h4 class="font-bold mb-2">🌊 Flood Safety</h4>
                        <p class="text-sm text-gray-600 mb-2">Preparing for floods and staying safe</p>
                        <div class="bg-gray-200 h-32 rounded flex items-center justify-center cursor-pointer hover:bg-gray-300 transition-colors">
                            <i class="fa-solid fa-play text-4xl text-orange-600"></i>
                        </div>
                    </div>
                    
                    <p class="text-xs text-gray-500 mt-2">Full video library available for registered users. <a href="#" class="text-orange-600 hover:underline">Login to access</a></p>
                </div>
            `
        }
    };
    
    if (resourceLinks) {
        resourceLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const resource = this.dataset.resource;
                
                if (resourceData[resource] && resourcePanel) {
                    resourceTitle.textContent = resourceData[resource].title;
                    resourceContent.innerHTML = resourceData[resource].content;
                    resourcePanel.classList.remove('hidden');
                }
            });
        });
    }
    
    if (closeResource && resourcePanel) {
        closeResource.addEventListener('click', function() {
            resourcePanel.classList.add('hidden');
        });
    }
    
    if (resourcePanel) {
        resourcePanel.addEventListener('click', function(e) {
            if (e.target === resourcePanel) {
                resourcePanel.classList.add('hidden');
            }
        });
    }

    // ===== MAP ZOOM CONTROLS =====
    const zoomIn = document.querySelector('.map-controls button:first-child');
    const zoomOut = document.querySelector('.map-controls button:last-child');
    
    if (zoomIn) {
        zoomIn.addEventListener('click', () => console.log('Zoom in clicked'));
    }
    if (zoomOut) {
        zoomOut.addEventListener('click', () => console.log('Zoom out clicked'));
    }
});