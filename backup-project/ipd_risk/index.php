<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPD Risk Monitor - Category 3 & 4</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #020617; /* slate-950 */
        }
        .glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .glass-hover:hover {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        @keyframes pulse-soft {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        .animate-pulse-soft {
            animation: pulse-soft 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
</head>
<body class="text-slate-200 min-h-screen">

    <!-- Top Navigation Bar -->
    <header class="sticky top-0 z-50 glass border-b border-white/10 px-8 py-4 flex justify-between items-center shadow-2xl">
        <div class="flex items-center space-x-5">
            <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 via-blue-600 to-purple-700 rounded-2xl flex items-center justify-center shadow-2xl shadow-blue-500/30 rotate-3">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-black tracking-tight bg-gradient-to-r from-white via-slate-300 to-slate-500 bg-clip-text text-transparent uppercase leading-none mb-1">
                    IPD Risk Monitor
                </h1>
                <div class="flex items-center space-x-2">
                    <span class="flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <p class="text-[10px] text-blue-400 font-bold uppercase tracking-[0.3em]">Category 3 & 4 Status / Live</p>
                </div>
            </div>
        </div>

        <div class="flex items-center space-x-8">
            <div class="flex flex-col items-end">
                <span class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mb-1">Total High-Risk</span>
                <div class="flex items-baseline space-x-1">
                    <span id="total-count" class="text-3xl font-black text-blue-500 leading-none">0</span>
                    <span class="text-xs text-slate-400 font-bold">Patients</span>
                </div>
            </div>
            <div class="h-10 w-px bg-white/10 hidden md:block"></div>
            <div class="hidden md:flex flex-col items-end">
                <span class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mb-1">Last Updated</span>
                <div id="last-update" class="text-xs font-mono text-emerald-400/80 bg-emerald-500/5 px-2 py-1 rounded border border-emerald-500/10">--:--:--</div>
            </div>
        </div>
    </header>

    <main class="p-8 max-w-[1600px] mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
            
            <!-- Male Wards Section -->
            <section id="male-section">
                <div class="flex items-center justify-between mb-8 group">
                    <div class="flex items-center space-x-4">
                        <div class="h-10 w-1.5 bg-blue-500 rounded-full"></div>
                        <div>
                            <h2 class="text-3xl font-black text-blue-400 tracking-tight">Male Patients</h2>
                            <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-0.5">ตึกผู้ป่วยในชาย</p>
                        </div>
                    </div>
                    <div class="bg-blue-500/10 text-blue-400 px-4 py-1.5 rounded-full text-sm font-black border border-blue-500/20 shadow-inner group-hover:bg-blue-500/20 transition-colors">
                        <span id="male-count">0</span> Cases
                    </div>
                </div>
                
                <div id="male-list" class="space-y-4">
                    <!-- Loading State -->
                    <div class="h-32 glass rounded-3xl animate-pulse-soft"></div>
                </div>
            </section>

            <!-- Female Wards Section -->
            <section id="female-section">
                <div class="flex items-center justify-between mb-8 group">
                    <div class="flex items-center space-x-4">
                        <div class="h-10 w-1.5 bg-rose-500 rounded-full"></div>
                        <div>
                            <h2 class="text-3xl font-black text-rose-400 tracking-tight">Female Patients</h2>
                            <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-0.5">ตึกผู้ป่วยในหญิง</p>
                        </div>
                    </div>
                    <div class="bg-rose-500/10 text-rose-400 px-4 py-1.5 rounded-full text-sm font-black border border-rose-500/20 shadow-inner group-hover:bg-rose-500/20 transition-colors">
                        <span id="female-count">0</span> Cases
                    </div>
                </div>

                <div id="female-list" class="space-y-4">
                    <!-- Loading State -->
                    <div class="h-32 glass rounded-3xl animate-pulse-soft"></div>
                </div>
            </section>

        </div>
    </main>

    <!-- Footer Decoration -->
    <div class="fixed bottom-0 left-0 right-0 h-1.5 bg-gradient-to-r from-blue-600 via-indigo-600 to-rose-600 opacity-50"></div>

    <script>
        async function updateDashboard() {
            try {
                const response = await fetch('api.php');
                const data = await response.json();

                if (data.error) {
                    console.error('API Error:', data.error);
                    return;
                }

                // Update Counts
                document.getElementById('total-count').textContent = data.total;
                document.getElementById('male-count').textContent = data.male.length;
                document.getElementById('female-count').textContent = data.female.length;
                document.getElementById('last-update').textContent = data.last_update;

                // Render Lists
                renderList('male-list', data.male);
                renderList('female-list', data.female);

            } catch (error) {
                console.error('Fetch Error:', error);
            }
        }

        function renderList(elementId, patients) {
            const container = document.getElementById(elementId);
            
            if (patients.length === 0) {
                container.innerHTML = `
                    <div class="border-2 border-dashed border-white/5 rounded-3xl h-48 flex flex-col items-center justify-center text-slate-700">
                        <svg class="w-10 h-10 mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span class="font-bold uppercase tracking-widest text-xs italic">No High-Risk Patients Found</span>
                    </div>
                `;
                return;
            }

            container.innerHTML = patients.map(p => {
                const isType4 = p.ipd_nurse_eval_range_code && p.ipd_nurse_eval_range_code.startsWith('4');
                const typeLabel = isType4 ? 'TYPE 4' : 'TYPE 3';
                const typeBg = isType4 ? 'bg-purple-600' : 'bg-rose-500';
                const typeShadow = isType4 ? 'rgba(147,51,234,0.3)' : 'rgba(244,63,94,0.3)';
                const typeBorder = isType4 ? 'border-purple-400/50' : 'border-rose-400/50';

                return `
                <div class="glass glass-hover rounded-2xl p-5 transition-all duration-500 shadow-xl relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/5 blur-3xl rounded-full -mr-16 -mt-16 group-hover:bg-blue-500/10 transition-colors"></div>
                    
                    <div class="flex justify-between items-start mb-4 relative z-10">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-2">
                                <span class="bg-indigo-500/10 text-indigo-400 text-[10px] font-black px-2 py-0.5 rounded border border-indigo-500/20 tracking-wider">HN: ${p.hn}</span>
                                <span class="bg-blue-500/10 text-blue-400 text-[10px] font-black px-2 py-0.5 rounded border border-blue-500/20 tracking-wider">AN: ${p.an}</span>
                            </div>
                            <h3 class="text-xl font-bold text-white group-hover:text-blue-200 transition-colors">
                                <span class="text-slate-400 font-medium mr-1">${p.pname}</span>${p.fname} ${p.lname}
                            </h3>
                        </div>
                        <div class="flex flex-col items-end">
                            <div class="${typeBg} text-white text-[10px] font-black px-2.5 py-1 rounded shadow-[0_0_15px_${typeShadow}] animate-pulse border ${typeBorder}">
                                ${typeLabel}
                            </div>
                            <div class="mt-2 text-[9px] text-slate-500 font-bold uppercase tracking-tighter italic">
                                Adm: ${new Date(p.regdate).toLocaleDateString('th-TH')}
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 pt-4 border-t border-white/5 relative z-10">
                        <!-- Diagnosis -->
                        <div class="md:col-span-7 flex items-start space-x-3">
                            <div class="mt-1 p-1.5 bg-emerald-500/10 rounded-lg">
                                <svg class="w-3.5 h-3.5 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-[9px] text-slate-500 uppercase font-black tracking-widest mb-0.5">Diagnosis</div>
                                <div class="text-xs text-emerald-300/90 font-semibold leading-relaxed">
                                    ${p.icd10 ? `<span class="text-emerald-500 font-black mr-1">[${p.icd10}]</span>` : ''}${p.diag_name || 'Pending Diagnosis'}
                                </div>
                            </div>
                        </div>

                        <!-- Doctor -->
                        <div class="md:col-span-3 flex items-start space-x-3">
                            <div class="mt-1 p-1.5 bg-sky-500/10 rounded-lg">
                                <svg class="w-3.5 h-3.5 text-sky-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-[9px] text-slate-500 uppercase font-black tracking-widest mb-0.5">Physician</div>
                                <div class="text-[11px] text-sky-200 font-bold">${p.doctor_name || 'Not assigned'}</div>
                            </div>
                        </div>

                        <!-- Bed -->
                        <div class="md:col-span-2 flex flex-col items-end">
                            <div class="text-[9px] text-slate-500 uppercase font-black tracking-widest mb-1.5">Bed No.</div>
                            <div class="bg-blue-600/20 text-blue-100 px-4 py-1 rounded-full text-xs font-black border border-blue-400/30 shadow-lg shadow-blue-500/5 group-hover:scale-110 transition-transform">
                                ${p.bedno || '-'}
                            </div>
                        </div>
                    </div>
                </div>
                `;
            }).join('');
        }

        // Initial fetch
        updateDashboard();
        // Set polling interval (30 seconds)
        setInterval(updateDashboard, 30000);
    </script>
</body>
</html>
