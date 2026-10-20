<template>
  <div class="min-h-screen text-slate-900">
    <div class="mx-auto max-w-[1440px] px-4 py-5 sm:px-6 lg:px-8">
      <header class="panel overflow-hidden p-5 sm:p-6">
        <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
          <div>
            <div class="flex flex-wrap items-center gap-2">
              <span class="brand-pill">ระบบอาหารผู้ป่วย</span>
              <span class="status-pill status-pill-soft">{{ currentDateLabel }}</span>
              <span class="status-pill status-pill-warn">ตัดรอบ {{ cutoffText }} น.</span>
              <span class="status-pill status-pill-info">HIS: {{ hisStatusLabel }}</span>
            </div>
            <h1 class="mt-4 text-3xl font-semibold text-slate-950 sm:text-4xl">
              แดชบอร์ดจัดการอาหารผู้ป่วย
            </h1>
            <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600 sm:text-base">
              ดึงข้อมูลผู้ป่วย เตียง BMI น้ำหนัก โรคประจำตัว และรายการอาหารจาก HIS พร้อมเลือกช่วงเวลาและแยกพื้นที่ตึกชาย/หญิง
            </p>
          </div>

          <nav class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4 xl:min-w-[440px]">
            <button
              v-for="scope in mainScopes"
              :key="scope.key"
              type="button"
              @click="activeScope = scope.key"
              :class="scopeButtonClass(scope.key)"
            >
              <span class="text-[11px] font-semibold uppercase text-slate-500">{{ scope.labelEn }}</span>
              <span class="mt-1 block text-sm font-semibold">{{ scope.labelTh }}</span>
            </button>
          </nav>
        </div>

        <div v-if="currentUser" class="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white/75 px-4 py-3 shadow-sm">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">ผู้ใช้งานปัจจุบัน</p>
            <p class="mt-1 text-sm font-semibold text-slate-950">{{ currentUser.name }} <span class="text-slate-500">({{ currentUser.role?.name }})</span></p>
          </div>
          <div class="flex gap-2 items-center flex-wrap">
            <button class="action-button bg-slate-200 hover:bg-slate-300 text-slate-700 shadow-sm border-transparent sm:w-auto" type="button" @click="activeScope = 'how_to'">📖 วิธีใช้งาน</button>
            <button class="action-button bg-slate-200 hover:bg-slate-300 text-slate-700 shadow-sm border-transparent sm:w-auto" type="button" @click="activeScope = 'about'">ℹ️ เกี่ยวกับระบบ</button>
            <button v-if="currentUser.role?.key === 'admin'" class="action-button bg-indigo-100 text-indigo-700 hover:bg-indigo-200 shadow-sm border-transparent sm:w-auto" type="button" @click="activeScope = 'data_dict'">🗄️ Data Dict</button>
            <button class="action-button action-button-danger sm:w-auto" type="button" @click="logout" :disabled="isAuthLoading">
              ออกจากระบบ
            </button>
          </div>
        </div>
      </header>

      <section v-if="!currentUser" class="panel mt-5 p-5 sm:p-6">
        <div class="mx-auto max-w-2xl">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <p class="section-kicker">{{ authMode === 'login' ? 'เข้าสู่ระบบ' : 'ลงทะเบียนผู้ใช้ใหม่' }}</p>
              <h2 class="mt-1 text-2xl font-semibold text-slate-950">{{ authMode === 'login' ? 'Hospital Meal Login' : 'สมัครใช้งานระบบอาหารผู้ป่วย' }}</h2>
            </div>
            <button class="action-button" type="button" @click="toggleAuthMode">
              {{ authMode === 'login' ? 'ลงทะเบียน' : 'กลับไปเข้าสู่ระบบ' }}
            </button>
          </div>
          <p class="mt-2 text-sm leading-6 text-slate-600">
            {{ authMode === 'login' ? 'กรุณาเข้าสู่ระบบก่อนใช้งาน dashboard และปุ่มซิงก์ HIS' : 'หลังลงทะเบียน บัญชีจะอยู่ในสถานะรอแอดมินอนุมัติก่อนเข้าใช้งาน' }}
          </p>

          <form v-if="authMode === 'login'" class="mt-5 grid gap-3" @submit.prevent="login">
            <label class="text-xs font-semibold text-slate-600">
              อีเมลหรือชื่อผู้ใช้
              <input v-model="loginForm.email" type="text" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm" autocomplete="username" />
            </label>
            <label class="text-xs font-semibold text-slate-600">
              รหัสผ่าน
              <input v-model="loginForm.password" type="password" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm" autocomplete="current-password" />
            </label>
            <button class="action-button action-button-primary" type="submit" :disabled="isAuthLoading">
              {{ isAuthLoading ? 'กำลังเข้าสู่ระบบ...' : 'เข้าสู่ระบบ' }}
            </button>
          </form>

          <form v-else class="mt-5 grid gap-3 sm:grid-cols-2" @submit.prevent="registerUser">
            <label class="text-xs font-semibold text-slate-600">
              ชื่อ
              <input v-model="registerForm.first_name" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm" autocomplete="given-name" />
            </label>
            <label class="text-xs font-semibold text-slate-600">
              สกุล
              <input v-model="registerForm.last_name" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm" autocomplete="family-name" />
            </label>
            <label class="text-xs font-semibold text-slate-600">
              ตำแหน่ง
              <input v-model="registerForm.position" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm" placeholder="เช่น พยาบาล, โภชนาการ" />
            </label>
            <label class="text-xs font-semibold text-slate-600">
              เบอร์โทร
              <input v-model="registerForm.phone" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm" autocomplete="tel" />
            </label>
            <label class="text-xs font-semibold text-slate-600">
              ชื่อผู้ใช้
              <input v-model="registerForm.username" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm" autocomplete="username" placeholder="ใช้ a-z, 0-9, -, _" />
            </label>
            <label class="text-xs font-semibold text-slate-600">
              รหัสผ่าน
              <input v-model="registerForm.password" type="password" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm" autocomplete="new-password" />
            </label>
            <label class="text-xs font-semibold text-slate-600 sm:col-span-2">
              ยืนยันรหัสผ่าน
              <input v-model="registerForm.password_confirmation" type="password" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm" autocomplete="new-password" />
            </label>
            <button class="action-button action-button-primary sm:col-span-2" type="submit" :disabled="isAuthLoading">
              {{ isAuthLoading ? 'กำลังส่งคำขอ...' : 'ลงทะเบียนและรออนุมัติ' }}
            </button>
          </form>

          <p v-if="authMessage" class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ authMessage }}
          </p>

        </div>
      </section>

      <template v-else>
      <section class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <MetricCard title="ผู้ป่วยในวอร์ด" :value="summaryCounts.patients" helper="ข้อมูล cache จาก HIS" tone="cyan" />
        <MetricCard title="มีรายการอาหาร" :value="mealSummary.totalPatientsWithMeals" helper="ในช่วงเวลาที่เลือก" tone="emerald" />
        <div @click="activeScope = 'no_meals'" class="cursor-pointer transition-transform hover:scale-[1.02] active:scale-95 group">
          <MetricCard title="ยังไม่มีรายการอาหาร" :value="patientsWithoutMeals.length" helper="คลิกเพื่อดูรายชื่อ" tone="amber" class="group-hover:ring-2 ring-amber-400" />
        </div>
        <MetricCard title="ผู้ป่วย NPO" :value="summaryCounts.npo" helper="งดอาหารและล็อกคำสั่งอาหาร" tone="rose" />
        <MetricCard title="BMI เสี่ยง" :value="summaryCounts.highRisk" helper="ต่ำกว่า 18.5 หรือมากกว่า 30" tone="amber" />
      </section>

      <section class="mt-5 space-y-5" v-show="isMainDashboardScope">
        <main class="min-w-0 space-y-5">
          <article class="panel p-5 sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
              <div>
                <p class="section-kicker">มุมมองการทำงาน</p>
                <h2 class="mt-1 text-2xl font-semibold text-slate-950">{{ currentScope.title }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ currentScope.description }}</p>
              </div>

              <div class="flex flex-wrap gap-2">
                <button class="action-button bg-indigo-600 text-white hover:bg-indigo-700 border-transparent shadow-md" type="button" @click="showReportModal = true">
                  🖨️ พิมพ์รายงานแยกตึก
                </button>
                <button class="action-button action-button-primary" type="button" @click="syncHis" :disabled="isSyncing">
                  {{ isSyncing ? 'กำลังซิงก์ HIS...' : 'ซิงก์ข้อมูล HIS' }}
                </button>
                <button class="action-button" type="button" @click="loadDashboard" :disabled="isLoading">
                  โหลดข้อมูลใหม่
                </button>
                <button class="action-button" type="button" @click="resetFilters">
                  ล้างตัวกรอง
                </button>
              </div>
            </div>

            <p v-if="errorMessage" class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
              {{ errorMessage }}
            </p>
            <p v-if="syncMessage" class="mt-4 rounded-lg border border-cyan-200 bg-cyan-50 px-4 py-3 text-sm text-cyan-700">
              {{ syncMessage }}
            </p>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
              <InfoChip label="วอร์ด" :value="dashboardTotals.wards" />
              <InfoChip label="ซิงก์ล่าสุด" :value="formatDateTime(syncHealth.last_sync)" />
              <InfoChip label="ตึกชาย" :value="wardSummary.male" />
              <InfoChip label="ตึกหญิง" :value="wardSummary.female" />
            </div>
          </article>

          <article class="panel p-5 sm:p-6">
            <div class="flex flex-col gap-4">
              <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div>
                  <p class="section-kicker">ตารางผู้ป่วยและอาหาร</p>
                  <h3 class="mt-1 text-xl font-semibold text-slate-950">เลือกช่วงเวลา แยกมื้อ และแยกตึกชาย/หญิง</h3>
                </div>

                <label class="search-shell">
                  <span class="search-icon" aria-hidden="true">ค้นหา</span>
                  <input
                    v-model="searchTerm"
                    type="search"
                    placeholder="ค้นหาชื่อ HN เตียง วอร์ด โรค หรืออาหาร"
                    class="search-input"
                  />
                </label>
              </div>

              <div class="rounded-lg border border-slate-200 bg-slate-50/80 p-3">
                <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">ช่วงเวลารายการอาหาร</p>
                    <p class="mt-1 text-sm text-slate-700">{{ dateRangeLabel }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ availableDateRangeLabel }}</p>
                  </div>

                  <div class="flex flex-wrap gap-2">
                    <button
                      v-for="option in datePresetOptions"
                      :key="option.key"
                      type="button"
                      :class="filterButtonClass(activeDatePreset === option.key)"
                      @click="setDatePreset(option.key)"
                    >
                      {{ option.label }}
                    </button>
                    <button type="button" :class="filterButtonClass(false)" @click="useAvailableDateRange">
                      ช่วงข้อมูลล่าสุด
                    </button>
                  </div>
                </div>

                <div v-if="activeDatePreset === 'custom'" class="mt-3 grid gap-3 sm:grid-cols-2">
                  <label class="text-xs font-semibold text-slate-600">
                    วันที่เริ่มต้น
                    <span class="mt-1 flex gap-2">
                      <input v-model="customDateStart" type="text" inputmode="numeric" placeholder="DD/MM/YYYY เช่น 24/12/2025" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm" />
                      <input type="date" :value="toDateInputValue(parseDateInput(customDateStart))" class="w-[145px] rounded-lg border border-slate-200 bg-white px-2 py-2 text-xs text-slate-700 shadow-sm" title="เลือกจากปฏิทิน" @input="customDateStart = toDisplayDateValue(parseDateInput($event.target.value))" />
                    </span>
                  </label>
                  <label class="text-xs font-semibold text-slate-600">
                    วันที่สิ้นสุด
                    <span class="mt-1 flex gap-2">
                      <input v-model="customDateEnd" type="text" inputmode="numeric" placeholder="DD/MM/YYYY เช่น 29/12/2025" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm" />
                      <input type="date" :value="toDateInputValue(parseDateInput(customDateEnd))" class="w-[145px] rounded-lg border border-slate-200 bg-white px-2 py-2 text-xs text-slate-700 shadow-sm" title="เลือกจากปฏิทิน" @input="customDateEnd = toDisplayDateValue(parseDateInput($event.target.value))" />
                    </span>
                  </label>
                </div>
              </div>

              <div class="flex flex-wrap gap-2">
                <button
                  v-for="option in mealFilterOptions"
                  :key="option.key"
                  type="button"
                  :class="filterButtonClass(activeMealFilter === option.key)"
                  @click="activeMealFilter = option.key"
                >
                  {{ option.label }}
                  <span class="ml-1 text-slate-500">{{ option.count }}</span>
                </button>
              </div>

              <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <SummaryRow label="ทั้งหมดในช่วง" :value="mealSummary.totalPatientsWithMeals" />
                <SummaryRow label="เช้า" :value="mealSummary.breakfast" />
                <SummaryRow label="กลางวัน" :value="mealSummary.lunch" />
                <SummaryRow label="เย็น" :value="mealSummary.dinner" />
              </div>
            </div>

            <div class="mt-5 grid gap-5 xl:grid-cols-2">
              <section
                v-for="group in groupedPatients"
                :key="group.key"
                :class="group.wrapperClass"
              >
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ group.caption }}</p>
                    <h4 class="mt-1 text-lg font-semibold text-slate-950">{{ group.label }}</h4>
                  </div>
                  <span class="status-pill status-pill-soft">{{ group.patients.length }} ราย</span>
                </div>

                <div class="overflow-x-auto overflow-y-auto max-h-[2500px] custom-scrollbar">
                  <div class="min-w-[500px]">
                    <div class="grid grid-cols-[1.2fr,1.5fr,0.8fr] border-b border-slate-200 bg-white/70 px-4 py-3 text-xs font-semibold uppercase text-slate-600">
                      <span>ผู้ป่วย / โภชนาการ</span>
                      <span>รายการอาหาร</span>
                      <span class="text-right">ประเภทอาหาร</span>
                    </div>

                    <div class="divide-y divide-slate-200">
                        <article
                          v-for="patient in group.patients"
                          :key="patient.hn"
                          class="grid grid-cols-[1.2fr,1.5fr,0.8fr] items-start gap-3 px-4 py-4 transition hover:bg-white/80"
                        >
                          <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                              <span class="bed-badge">{{ patient.bed_label }}</span>
                              <h5 class="truncate text-sm font-semibold text-slate-950">{{ patient.full_name }}</h5>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">
                              HN {{ patient.hn }} • AN {{ patient.an || '-' }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                              {{ patient.ward_name }} / {{ patient.room_name }}
                            </p>
                            
                            <div class="mt-3 flex flex-wrap gap-1.5">
                              <span v-if="patient.npo_status" class="status-pill status-pill-danger">NPO</span>
                              <span v-if="patient.weight_kg" class="status-pill status-pill-soft">น้ำหนัก {{ formatNumber(patient.weight_kg) }} กก.</span>
                              <span v-if="patient.bmi" :class="patient.bmi_warning ? 'status-pill status-pill-warn' : 'status-pill status-pill-soft'">
                                BMI {{ formatNumber(patient.bmi) }}
                              </span>
                              <span v-if="patient.chronic_diseases" class="status-pill status-pill-info">โรคประจำตัว: {{ patient.chronic_diseases }}</span>
                              <span v-for="allergen in patient.allergies" :key="allergen" class="status-pill status-pill-info">แพ้: {{ allergen }}</span>
                            </div>
                          </div>

                          <div class="space-y-2 text-xs text-slate-700 min-w-0">
                            <div v-for="meal in visibleMeals(patient)" :key="`${meal.key}-${meal.date || 'none'}`" class="rounded-lg border border-slate-200 bg-white/75 px-3 py-2 shadow-sm">
                              <p class="font-semibold text-slate-950">{{ meal.label }}</p>
                              <p class="mt-1 leading-5 text-slate-700">{{ meal.value }}</p>
                            </div>
                            
                            <div class="mt-3 flex flex-wrap gap-2">
                              <div v-if="patient.therapeutic_diet" class="inline-flex rounded-full border border-cyan-200 bg-cyan-50 px-3 py-1 font-semibold text-cyan-800">
                                เฉพาะโรค: {{ patient.therapeutic_diet }}
                              </div>
                              <div v-if="patient.specific_group_diet" class="inline-flex rounded-full border border-orange-200 bg-orange-50 px-3 py-1 font-semibold text-orange-800">
                                เฉพาะกลุ่ม: {{ patient.specific_group_diet }}
                              </div>
                              <div v-if="patient.food_allergy" class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-3 py-1 font-semibold text-rose-800">
                                แพ้/งด: {{ patient.food_allergy }}
                              </div>
                            </div>
                          </div>

                        <div class="text-right">
                          <span :class="['inline-flex rounded-full border px-3 py-1 text-xs font-semibold shadow-sm', dietOrderClass(patient.diet_order)]">
                            {{ patient.diet_order || '-' }}
                          </span>
                        </div>
                      </article>
                    </div>
                  </div>
                </div>
              </section>

              <div v-if="!isLoading && groupedPatients.length === 0" class="rounded-lg border border-slate-200 bg-white/80 px-4 py-10 text-center text-sm text-slate-600 shadow-sm">
                ไม่พบรายการที่ตรงกับช่วงเวลาที่เลือก
                <button type="button" class="ml-2 text-cyan-700 underline underline-offset-4" @click="useAvailableDateRange">
                  กลับไปช่วงข้อมูลล่าสุด
                </button>
              </div>

              <div v-if="isLoading" class="rounded-lg border border-slate-200 bg-white/80 px-4 py-10 text-center text-sm text-slate-600 shadow-sm">
                กำลังโหลดข้อมูล...
              </div>
            </div>
          </article>
        </main>

        <aside class="space-y-5 min-w-0">
          <!-- Row 1: 3 Columns -->
          <div class="grid gap-5 lg:grid-cols-3 items-start">
            <article class="panel p-5 sm:p-6">
              <p class="section-kicker">สรุปตามตึก</p>
              <h3 class="mt-1 text-xl font-semibold text-slate-950">พื้นที่ชาย/หญิง</h3>
              <div class="mt-5 space-y-3">
                <SummaryRow label="ตึกผู้ป่วยในชาย" :value="wardSummary.male" />
                <SummaryRow label="ตึกผู้ป่วยในหญิง" :value="wardSummary.female" />
                <SummaryRow label="พื้นที่อื่น" :value="wardSummary.other" />
                <SummaryRow label="รวมที่แสดง" :value="filteredPatients.length" />
              </div>
            </article>

            <article class="panel p-5 sm:p-6">
              <p class="section-kicker">สรุปรายการอาหาร</p>
              <h3 class="mt-1 text-xl font-semibold text-slate-950">ตามช่วงเวลาที่เลือก</h3>
              <div class="mt-5 space-y-3">
                <SummaryRow label="มีอาหารเช้า" :value="mealSummary.breakfast" />
                <SummaryRow label="มีอาหารกลางวัน" :value="mealSummary.lunch" />
                <SummaryRow label="มีอาหารเย็น" :value="mealSummary.dinner" />
                <SummaryRow label="มีอาหารหลัก/รอบอื่น" :value="mealSummary.main" />
              </div>
            </article>

            <article class="panel p-5 sm:p-6">
              <p class="section-kicker">คำสั่งลัด</p>
              <h3 class="mt-1 text-xl font-semibold text-slate-950">งานที่ใช้บ่อย</h3>
              <div class="mt-5 grid gap-3">
                <button class="action-button action-button-primary" type="button" @click="performQuickAction('ใช้ template กับผู้ป่วยหลายคน')">ใช้ template แบบกลุ่ม</button>
                <button class="action-button" type="button" @click="performQuickAction('คัดลอกคำสั่งจากวันก่อน')">คัดลอกจากวันก่อน</button>
                <button class="action-button" type="button" @click="performQuickAction('ส่งสรุปผ่าน Telegram')">ส่งสรุป Telegram</button>
                <button class="action-button action-button-danger" type="button" @click="performQuickAction('สร้างคำสั่ง STAT')">สร้างคำสั่ง STAT</button>
              </div>
              <p v-if="quickActionMessage" class="mt-4 text-sm text-slate-600">{{ quickActionMessage }}</p>
            </article>
          </div>

          <!-- Row 2: 1 Column -->
          <div class="grid gap-5 lg:grid-cols-1 items-start">
            <article v-if="canManageTemplates" class="panel p-5 sm:p-6">
              <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div>
                  <p class="section-kicker">Diet Template</p>
                  <h3 class="mt-1 text-xl font-semibold text-slate-950">สร้างเทมเพลตสั่งอาหาร</h3>
                  <p class="mt-2 text-sm leading-6 text-slate-600">
                    สร้างชุดอาหารไว้ใช้กับ Manual Order โดยกำหนดประเภทอาหาร มื้อที่ใช้ได้ และรายการอาหารในชุด
                  </p>
                </div>
                <button class="action-button" type="button" @click="loadDietTemplateReferences" :disabled="isTemplateSaving">
                  โหลดประเภท/เทมเพลต
                </button>
              </div>

              <form class="mt-5 grid gap-3" @submit.prevent="saveDietTemplate">
                <div class="grid gap-3 sm:grid-cols-2">
                  <label class="grid gap-1 text-xs font-semibold text-slate-600">
                    รหัสเทมเพลต
                    <input v-model="templateForm.code" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm" placeholder="เช่น SOFT-RICE" />
                  </label>
                  <label class="grid gap-1 text-xs font-semibold text-slate-600">
                    ชื่อเทมเพลต
                    <input v-model="templateForm.name" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm" placeholder="เช่น อาหารอ่อน ข้าวต้ม" />
                  </label>
                </div>

                <label class="grid gap-1 text-xs font-semibold text-slate-600">
                  ประเภทอาหาร
                  <select v-model="templateForm.diet_type_id" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm">
                    <option value="">เลือกประเภทอาหาร</option>
                    <option v-for="type in dietTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
                  </select>
                </label>

                <label class="grid gap-1 text-xs font-semibold text-slate-600">
                  รายละเอียด
                  <textarea v-model="templateForm.description" class="min-h-20 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm" placeholder="คำอธิบาย เงื่อนไข หรือข้อควรระวัง"></textarea>
                </label>

                <div class="rounded-lg border border-slate-200 bg-white/80 p-3">
                  <p class="text-xs font-semibold text-slate-600">มื้อที่ใช้เทมเพลตนี้ได้</p>
                  <div class="mt-3 grid gap-2 sm:grid-cols-3">
                    <label v-for="meal in manualMealOptions" :key="meal.key" class="flex items-center gap-2 text-sm text-slate-700">
                      <input v-model="templateForm.meal_scope" :value="meal.key" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-cyan-600" />
                      {{ meal.label }}
                    </label>
                  </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white/80 p-3">
                  <div class="flex items-center justify-between gap-3">
                    <p class="text-xs font-semibold text-slate-600">รายการอาหารในเทมเพลต</p>
                    <button class="action-button !w-auto !px-3 !py-2" type="button" @click="addTemplateItem">เพิ่มรายการ</button>
                  </div>

                  <div class="mt-3 grid gap-3">
                    <div v-for="(item, index) in templateForm.items" :key="index" class="grid gap-2 rounded-lg border border-slate-200 bg-slate-50 p-3">
                      <div class="grid gap-2 sm:grid-cols-[120px,1fr,120px]">
                        <input v-model="item.item_type" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm" placeholder="ประเภท" />
                        <input v-model="item.item_name" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm" placeholder="ชื่ออาหาร" />
                        <input v-model="item.quantity" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm" placeholder="จำนวน" />
                      </div>
                      <div class="grid gap-2 sm:grid-cols-[1fr,90px]">
                        <input v-model="item.notes" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm" placeholder="หมายเหตุรายการนี้" />
                        <button class="action-button action-button-danger !py-2" type="button" @click="removeTemplateItem(index)" :disabled="templateForm.items.length === 1">
                          ลบ
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <button class="action-button action-button-primary" type="submit" :disabled="isTemplateSaving">
                  {{ isTemplateSaving ? 'กำลังสร้าง...' : 'สร้างเทมเพลต' }}
                </button>
              </form>

              <p v-if="templateMessage" class="mt-4 text-sm text-slate-600">{{ templateMessage }}</p>
            </article>

            <article v-if="canManualOrder" class="panel p-5 sm:p-6">
              <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div>
                  <p class="section-kicker">Manual Order</p>
                  <h3 class="mt-1 text-xl font-semibold text-slate-950">สั่งอาหารเองผ่านระบบ</h3>
                  <p class="mt-2 text-sm leading-6 text-slate-600">
                    ใช้ในกรณีไม่ดึงจาก HIS โดยยังคงรายการอาหาร HIS เดิมไว้ และกันสั่งหากเตียงเป็น NPO/งดน้ำ/งดอาหาร
                  </p>
                </div>
                <button class="action-button" type="button" @click="loadDietTemplateReferences" :disabled="isManualOrdering">
                  โหลดเทมเพลต
                </button>
              </div>

              <form class="mt-5 grid gap-4" @submit.prevent="submitManualOrder">
                <label class="grid gap-1 text-xs font-semibold text-slate-600">
                  วันที่สั่งอาหาร
                  <span class="flex gap-2">
                    <input v-model="manualOrderForm.service_date" type="text" inputmode="numeric" placeholder="DD/MM/YYYY" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm" />
                    <input type="date" :value="toDateInputValue(parseDateInput(manualOrderForm.service_date))" class="w-[145px] rounded-lg border border-slate-200 bg-white px-2 py-2 text-xs text-slate-700 shadow-sm" title="เลือกจากปฏิทิน" @input="manualOrderForm.service_date = toDisplayDateValue(parseDateInput($event.target.value))" />
                  </span>
                </label>

                <label class="grid gap-1 text-xs font-semibold text-slate-600">
                  เลือกอาหารตามเทมเพลต
                  <select v-model="manualOrderForm.template_id" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm">
                    <option value="">เลือกเทมเพลต</option>
                    <option v-for="template in dietTemplates" :key="template.id" :value="template.id">{{ template.name }}</option>
                  </select>
                </label>

                <div class="rounded-lg border border-slate-200 bg-white/80 p-3">
                  <p class="text-xs font-semibold text-slate-600">มื้ออาหาร</p>
                  <label class="mt-3 flex items-center gap-2 text-sm text-slate-700">
                    <input v-model="manualOrderAllMeals" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-cyan-600" />
                    สั่งครั้งเดียวครบ 3 มื้อ
                  </label>
                  <div class="mt-3 grid gap-2 sm:grid-cols-3">
                    <label v-for="meal in manualMealOptions" :key="meal.key" class="flex items-center gap-2 text-sm text-slate-700">
                      <input v-model="manualOrderForm.meal_types" :value="meal.key" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-cyan-600" />
                      {{ meal.label }}
                    </label>
                  </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white/80 p-3">
                  <p class="text-xs font-semibold text-slate-600">เลือกเป้าหมาย</p>
                  <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                      <input v-model="manualOrderMode" value="ward" type="radio" class="h-4 w-4 border-slate-300 text-cyan-600" />
                      สั่งเป็นชุด/ตึกตามรายการที่แสดง
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                      <input v-model="manualOrderMode" value="beds" type="radio" class="h-4 w-4 border-slate-300 text-cyan-600" />
                      เลือกรายการเตียงเอง
                    </label>
                  </div>

                  <div class="mt-4 space-y-6">
                    <div v-for="group in groupedManualSelectablePatients" :key="group.key">
                      <div class="mb-3 flex items-center gap-3">
                        <h4 class="text-sm font-bold text-slate-800">{{ group.label }}</h4>
                        <div class="h-px flex-1 bg-slate-200"></div>
                      </div>
                      
                      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2">
                        <label
                          v-for="patient in group.patients"
                          :key="patient.id"
                          :class="[
                            'flex flex-col rounded-lg border px-2 py-2 text-xs transition-colors cursor-pointer',
                            patient.meal_blocked ? 'border-rose-200 bg-rose-50 text-rose-700 opacity-60' : 
                            patient.ward_group === 'male' ? 'border-sky-200 bg-sky-50 text-sky-900 hover:bg-sky-100' : 
                            patient.ward_group === 'female' ? 'border-pink-200 bg-pink-50 text-pink-900 hover:bg-pink-100' : 'border-slate-200 bg-slate-50 text-slate-700'
                          ]"
                        >
                          <div class="flex items-start gap-1.5">
                            <input
                              v-model="manualOrderForm.patient_ids"
                              :value="patient.id"
                              :disabled="patient.meal_blocked || manualOrderMode === 'ward'"
                              type="checkbox"
                              class="mt-0.5 h-3.5 w-3.5 rounded border-slate-300 text-cyan-600 shrink-0"
                            />
                            <span class="font-bold truncate" :title="patient.bed_label">{{ patient.bed_label }}</span>
                          </div>
                          <span class="truncate mt-1 font-medium" :title="patient.full_name">{{ patient.full_name }}</span>
                          <span v-if="patient.meal_blocked" class="mt-1 text-[10px] font-semibold leading-tight text-rose-700">
                            {{ patient.meal_block_reason }}
                          </span>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>

                <button class="action-button action-button-primary" type="submit" :disabled="isManualOrdering">
                  {{ isManualOrdering ? 'กำลังบันทึก...' : 'บันทึกคำสั่งอาหาร Manual' }}
                </button>
              </form>

              <div v-if="manualOrderMessage" class="mt-4 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-700">
                <p>{{ manualOrderMessage }}</p>
                <div v-if="manualSkippedPatients.length" class="mt-2">
                  <p class="font-semibold text-rose-700">รายการที่ถูกข้าม</p>
                  <p v-for="patient in manualSkippedPatients" :key="`${patient.hn}-${patient.reason}`" class="text-xs text-rose-700">
                    {{ patient.bed || '-' }} {{ patient.name }}: {{ patient.reason }}
                  </p>
                </div>
              </div>
            </article>
          </div>

          <!-- Row 3: 2 Columns -->
          <div class="grid gap-5 lg:grid-cols-2 items-start">
            <article v-if="canManageUsers" class="panel p-5 sm:p-6">
              <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                  <div>
                    <p class="section-kicker">Telegram Alert</p>
                    <h3 class="mt-1 text-xl font-semibold text-slate-950">ตั้งเวลาแจ้งเตือน 3 มื้อ</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                      แอดมินสามารถเปิด/ปิดและกำหนดเวลาแจ้งเตือนผ่าน Telegram ได้ 3 รอบต่อวัน
                    </p>
                  </div>
                  <button class="action-button" type="button" @click="loadTelegramSchedules" :disabled="isTelegramLoading">
                    โหลดการตั้งค่า
                  </button>
                </div>

                <div class="mt-4 rounded-lg border border-cyan-200 bg-cyan-50 px-4 py-3 text-xs leading-6 text-cyan-800">
                  <p>สถานะ Telegram: {{ telegramStatusLabel }}</p>
                  <p>ระบบ scheduler จะตรวจทุก 1 นาที และส่งซ้ำได้ไม่เกิน 1 ครั้งต่อมื้อต่อวัน</p>
                </div>

                <form class="mt-5 grid gap-3 rounded-lg border border-slate-200 bg-white/80 p-4 shadow-sm" @submit.prevent="saveTelegramConfig">
                  <p class="text-sm font-semibold text-slate-900">ตั้งค่าการเชื่อมต่อ (บันทึกลง .env)</p>
                  <label class="flex items-center gap-3 text-sm text-slate-700">
                    <input v-model="telegramConfig.enabled" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-cyan-600" />
                    เปิดใช้งาน Telegram Notification
                  </label>
                  <input v-model="telegramConfig.bot_token" type="text" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm" placeholder="Bot Token (e.g. 123456789:ABCDefG...)" />
                  <input v-model="telegramConfig.chat_id" type="text" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm" placeholder="Chat ID (e.g. -100123456789)" />
                  
                  <button class="action-button mt-1" type="submit" :disabled="isTelegramLoading">
                    {{ isTelegramLoading ? 'กำลังบันทึก...' : 'บันทึกการตั้งค่าระบบ' }}
                  </button>
                </form>

                <form class="mt-5 grid gap-3" @submit.prevent="saveTelegramSchedules">
                  <div
                    v-for="schedule in telegramSchedules"
                    :key="schedule.key"
                    class="grid gap-3 rounded-lg border border-slate-200 bg-white/80 p-3 shadow-sm sm:grid-cols-[1fr,150px,120px]"
                  >
                    <label class="flex items-center gap-3 text-sm font-semibold text-slate-700">
                      <input v-model="schedule.enabled" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-cyan-600" />
                      {{ schedule.label }}
                    </label>
                    <input v-model="schedule.notify_at" type="text" placeholder="HH:MM (24 ชม.)" maxlength="5" pattern="^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$" title="กรุณากรอกเวลาให้ถูกต้อง เช่น 13:30" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm" />
                    <button class="action-button" type="button" @click="testTelegramSchedule(schedule.key)" :disabled="isTelegramLoading">
                      ทดสอบ
                    </button>
                  </div>

                  <button class="action-button action-button-primary" type="submit" :disabled="isTelegramLoading">
                    {{ isTelegramLoading ? 'กำลังบันทึก...' : 'บันทึกเวลาแจ้งเตือน' }}
                  </button>
                </form>

                <p v-if="telegramMessage" class="mt-4 text-sm text-slate-600">{{ telegramMessage }}</p>
              </article>

              <article v-if="canManageUsers" class="panel p-5 sm:p-6">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div>
                  <p class="section-kicker">จัดการผู้ใช้และ Role</p>
                  <h3 class="mt-1 text-xl font-semibold text-slate-950">เพิ่มผู้ใช้ / เปลี่ยนสิทธิ์</h3>
                </div>
                <button class="action-button" type="button" @click="loadUserManagement">
                  โหลดรายชื่อผู้ใช้
                </button>
              </div>

              <form class="mt-5 grid gap-3 sm:grid-cols-2" @submit.prevent="saveUser">
                <input v-model="userForm.name" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm" placeholder="ชื่อ" />
                <input v-model="userForm.email" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm" placeholder="อีเมล" />
                <input v-model="userForm.username" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm" placeholder="ชื่อผู้ใช้" />
                <input v-model="userForm.password" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm" placeholder="รหัสผ่าน (ใส่เมื่อต้องการเปลี่ยน)" type="password" />
                <input v-model="userForm.position" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm" placeholder="ตำแหน่ง" />
                <input v-model="userForm.phone" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm" placeholder="เบอร์โทร" />
                <select v-model="userForm.role_id" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm">
                  <option value="">เลือก role</option>
                  <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                </select>
                <select v-model="userForm.approval_status" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm">
                  <option value="active">อนุมัติแล้ว</option>
                  <option value="pending">รออนุมัติ</option>
                  <option value="rejected">ปฏิเสธ</option>
                </select>
                <button class="action-button action-button-primary sm:col-span-2" type="submit">{{ userForm.id ? 'บันทึก' : 'เพิ่ม' }}</button>
              </form>

              <p v-if="userMessage" class="mt-4 text-sm text-slate-600">{{ userMessage }}</p>

              <div class="mt-5 overflow-x-auto overflow-y-auto max-h-[1200px] rounded-lg border border-slate-200 bg-white/80 shadow-sm custom-scrollbar">
                <div class="min-w-[980px] divide-y divide-slate-200">
                  <div v-for="user in users" :key="user.id" class="grid grid-cols-[1fr,1fr,0.8fr,0.8fr,0.8fr,auto] items-center gap-3 px-4 py-3 text-sm">
                    <div>
                      <p class="font-semibold text-slate-950">{{ user.name }}</p>
                      <p class="text-xs text-slate-500">{{ user.username || user.email }}</p>
                    </div>
                    <span class="text-slate-600">{{ user.email }}</span>
                    <span class="text-slate-600">{{ user.position || '-' }}</span>
                    <span class="text-slate-600">{{ user.phone || '-' }}</span>
                    <div class="flex flex-wrap gap-2">
                      <span class="status-pill status-pill-soft">{{ user.role?.name ?? '-' }}</span>
                      <span :class="approvalStatusClass(user.approval_status)">{{ approvalStatusLabel(user.approval_status) }}</span>
                    </div>
                    <div class="flex flex-wrap justify-end gap-2">
                      <button v-if="user.approval_status === 'pending'" class="action-button action-button-primary" type="button" @click="approveUser(user)">อนุมัติ</button>
                      <button v-if="user.approval_status === 'pending'" class="action-button action-button-danger" type="button" @click="rejectUser(user)">ปฏิเสธ</button>
                      <button class="action-button" type="button" @click="editUser(user)">แก้ไข</button>
                    </div>
                  </div>
                </div>
              </div>
            </article>
          </div>
        </aside>
      </section>

      <!-- No Meals Dedicated Page -->
      <section class="mt-5" v-if="activeScope === 'no_meals'">
        <article class="panel p-5 sm:p-6">
          <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
              <p class="section-kicker">รายชื่อผู้ป่วย</p>
              <h3 class="mt-1 text-xl font-semibold text-slate-950">ยังไม่มีรายการอาหาร</h3>
              <p class="mt-2 text-sm leading-6 text-slate-600">ผู้ป่วยในระบบที่ยังไม่ได้ระบุออเดอร์อาหารในช่วงเวลาที่เลือก</p>
            </div>
            <div class="flex gap-2">
              <button class="action-button action-button-primary shadow-sm" type="button" @click="activeScope = 'dashboard'">
                ← กลับหน้าหลัก
              </button>
            </div>
          </div>

          <div class="mt-5 overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm custom-scrollbar max-h-[800px]">
            <table class="w-full text-left text-sm whitespace-nowrap">
              <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold sticky top-0 shadow-sm z-10">
                <tr>
                  <th class="px-5 py-3">เตียง</th>
                  <th class="px-5 py-3">ชื่อ-สกุล</th>
                  <th class="px-5 py-3">ตึกผู้ป่วย</th>
                  <th class="px-5 py-3">สถานะพิเศษ</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="patient in patientsWithoutMeals" :key="patient.id" class="hover:bg-slate-50 transition-colors">
                  <td class="px-5 py-3 font-bold text-slate-700">{{ patient.bed_label }}</td>
                  <td class="px-5 py-3 font-medium text-slate-900">{{ patient.full_name }} <span class="text-[11px] text-slate-500 ml-1">(HN: {{ patient.hn }})</span></td>
                  <td class="px-5 py-3 text-slate-600">{{ patient.ward_name }}</td>
                  <td class="px-5 py-3">
                    <span v-if="patient.npo_status" class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 border border-rose-200">NPO</span>
                    <span v-else-if="patient.food_allergy" class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700 border border-amber-200">แพ้: {{ patient.food_allergy }}</span>
                    <span v-else class="text-slate-300">-</span>
                  </td>
                </tr>
                <tr v-if="patientsWithoutMeals.length === 0">
                  <td colspan="4" class="px-5 py-8 text-center text-slate-500">เยี่ยมมาก! ผู้ป่วยทุกคนมีรายการอาหารครบแล้ว</td>
                </tr>
              </tbody>
            </table>
          </div>
        </article>
      </section>

      <!-- How To Page -->
      <section class="mt-5" v-if="activeScope === 'how_to'">
        <article class="panel p-5 sm:p-8 max-w-4xl mx-auto">
          <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
            <h2 class="text-2xl font-bold text-slate-900">📖 คู่มือการใช้งานระบบ Hospital Meal System</h2>
            <button class="action-button action-button-primary shadow-sm" type="button" @click="activeScope = 'dashboard'">
              ← กลับหน้าหลัก
            </button>
          </div>
          
          <div class="space-y-8 text-slate-700 leading-relaxed">
            <section>
              <h3 class="text-lg font-semibold text-indigo-700 mb-3">1. ภาพรวมระบบและความสามารถหลัก</h3>
              <p>ระบบ <strong>Hospital Meal System</strong> ถูกออกแบบมาเพื่อเชื่อมโยงข้อมูลผู้ป่วยจากระบบ HIS ของโรงพยาบาล เข้ากับการจัดการอาหารของฝ่ายโภชนาการและตึกผู้ป่วย โดยมีความสามารถหลักดังนี้:</p>
              <ul class="list-disc pl-6 mt-2 space-y-2">
                <li>ซิงก์ข้อมูลผู้ป่วยแบบเรียลไทม์ (HN, ชื่อ, เตียง, ตึก, สถานะ NPO, ข้อมูลแพ้อาหาร)</li>
                <li>ดูและกรองข้อมูลผู้ป่วยแยกตามตึกชาย/หญิง หรือตามมื้ออาหาร</li>
                <li>ระบบแจ้งเตือน Telegram อัตโนมัติตามมื้ออาหาร (เช้า, กลางวัน, เย็น)</li>
                <li>บันทึกและจัดการคำสั่งอาหาร ทั้งแบบดึงอัตโนมัติหรือสั่ง Manual</li>
                <li>ออกรายงานสรุปยอดอาหาร (Export เป็น PDF และ Excel) เพื่อใช้ในห้องครัว</li>
              </ul>
            </section>

            <section>
              <h3 class="text-lg font-semibold text-indigo-700 mb-3">2. การดูข้อมูลแดชบอร์ด</h3>
              <p>เมื่อเข้าสู่ระบบ หน้าแรกจะแสดง <strong>แดชบอร์ดสรุป</strong> ซึ่งจะมีกล่องตัวเลขสำคัญ (Metric Cards) บอกจำนวนผู้ป่วย, คนที่มีรายการอาหารแล้ว, ผู้ป่วย NPO และผู้ป่วยที่ BMI เสี่ยง คุณสามารถคลิกที่ปุ่มต่างๆ เพื่อเปลี่ยนมุมมอง (Scope) เช่น มุมมองของพยาบาล, โภชนาการ หรือห้องครัว</p>
            </section>

            <section>
              <h3 class="text-lg font-semibold text-indigo-700 mb-3">3. การจัดการคำสั่งอาหาร (สำหรับพยาบาล/โภชนาการ)</h3>
              <p>สามารถทำได้โดยไปที่มุมมอง <strong>NURSE (ศูนย์สั่งอาหารพยาบาล)</strong> ด้านล่างจะมีตารางรายชื่อ คุณสามารถค้นหาชื่อผู้ป่วย และคลิกเลือกรายการอาหารที่ต้องการ ระบบจะบันทึกอัตโนมัติ หรือใช้ปุ่ม "สั่งอาหารด่วน" สำหรับการลงรายการแบบ Manual ให้ผู้ป่วยเฉพาะราย</p>
            </section>

            <section>
              <h3 class="text-lg font-semibold text-indigo-700 mb-3">4. การพิมพ์รายงานสำหรับห้องครัว</h3>
              <p>คลิกที่ปุ่ม <strong>"🖨️ พิมพ์รายงานแยกตึก"</strong> ที่หน้าหลัก จะมีหน้าต่างขึ้นมาให้เลือกวันที่และมื้ออาหารที่ต้องการพิมพ์ จากนั้นกดปุ่ม Export Excel หรือ Export PDF ระบบจะสร้างไฟล์รายงานที่จัดเรียงข้อมูล เตียง, ชื่อ, ประเภทอาหาร, และสถานะต่างๆ ไว้อย่างสวยงามและพร้อมพิมพ์ทันที</p>
            </section>
          </div>
        </article>
      </section>

      <!-- About Page -->
      <section class="mt-5" v-if="activeScope === 'about'">
        <article class="panel p-8 max-w-2xl mx-auto text-center">
          <div class="mb-6 flex justify-center">
            <div class="h-24 w-24 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center text-4xl shadow-inner">
              🏥
            </div>
          </div>
          <h2 class="text-2xl font-bold text-slate-900 mb-2">ระบบจัดการอาหารผู้ป่วย (Hospital Meal System)</h2>
          <p class="text-slate-500 mb-8">เวอร์ชัน 1.0.0</p>
          
          <div class="bg-slate-50 rounded-xl p-6 border border-slate-100 text-left mb-8 shadow-sm">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500 mb-4">ข้อมูลผู้พัฒนาระบบ</h3>
            <div class="flex items-start gap-4">
              <div class="h-12 w-12 bg-slate-200 rounded-full flex items-center justify-center text-xl shrink-0">
                👨‍💻
              </div>
              <div>
                <p class="text-lg font-bold text-slate-900">ศุภชัย นะที</p>
                <p class="text-indigo-600 font-medium mt-1">ตำแหน่ง: นักวิชาการคอมพิวเตอร์</p>
                <p class="text-slate-600 mt-1">โรงพยาบาลเทพรัตนเวชชานุกูล เฉลิมพระเกียรติ ๖๐ พรรษา</p>
              </div>
            </div>
          </div>
          
          <button class="action-button action-button-primary shadow-sm px-8" type="button" @click="activeScope = 'dashboard'">
            กลับหน้าหลัก
          </button>
        </article>
      </section>

      <!-- Data Dictionary Page -->
      <section class="mt-5" v-if="activeScope === 'data_dict' && currentUser?.role?.key === 'admin'">
        <article class="panel p-5 sm:p-8 max-w-5xl mx-auto">
          <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
            <div>
              <h2 class="text-2xl font-bold text-slate-900">🗄️ Data Dictionary</h2>
              <p class="text-sm text-slate-500 mt-1">โครงสร้างฐานข้อมูลหลัก (สำหรับผู้ดูแลระบบ)</p>
            </div>
            <button class="action-button action-button-primary shadow-sm" type="button" @click="activeScope = 'dashboard'">
              ← กลับหน้าหลัก
            </button>
          </div>
          
          <div class="grid gap-6 md:grid-cols-2 text-sm text-slate-700">
            <!-- Table: patients -->
            <div class="border border-slate-200 rounded-lg overflow-hidden shadow-sm">
              <div class="bg-indigo-50 px-4 py-3 border-b border-slate-200 font-bold text-indigo-900">
                Table: patients (ข้อมูลผู้ป่วยจาก HIS)
              </div>
              <ul class="divide-y divide-slate-100">
                <li class="px-4 py-2 flex justify-between"><span class="font-mono text-xs font-semibold text-slate-900">hn</span><span>รหัสประจำตัวผู้ป่วย</span></li>
                <li class="px-4 py-2 flex justify-between"><span class="font-mono text-xs font-semibold text-slate-900">an</span><span>รหัสการเข้ารับการรักษา (Admit)</span></li>
                <li class="px-4 py-2 flex justify-between"><span class="font-mono text-xs font-semibold text-slate-900">full_name</span><span>ชื่อ-นามสกุลผู้ป่วย</span></li>
                <li class="px-4 py-2 flex justify-between"><span class="font-mono text-xs font-semibold text-slate-900">bed_label / ward_name</span><span>เตียง และ ตึกที่รักษา</span></li>
                <li class="px-4 py-2 flex justify-between"><span class="font-mono text-xs font-semibold text-slate-900">npo_status</span><span>สถานะงดน้ำและอาหาร (Boolean)</span></li>
                <li class="px-4 py-2 flex justify-between"><span class="font-mono text-xs font-semibold text-slate-900">bmi / bw / ht</span><span>ค่า BMI, น้ำหนัก, ส่วนสูง</span></li>
                <li class="px-4 py-2 flex justify-between"><span class="font-mono text-xs font-semibold text-slate-900">food_allergy</span><span>ประวัติการแพ้อาหาร</span></li>
              </ul>
            </div>
            
            <!-- Table: meal_orders -->
            <div class="border border-slate-200 rounded-lg overflow-hidden shadow-sm">
              <div class="bg-emerald-50 px-4 py-3 border-b border-slate-200 font-bold text-emerald-900">
                Table: meal_orders (คำสั่งอาหาร)
              </div>
              <ul class="divide-y divide-slate-100">
                <li class="px-4 py-2 flex justify-between"><span class="font-mono text-xs font-semibold text-slate-900">patient_id</span><span>FK เชื่อมโยงผู้ป่วย</span></li>
                <li class="px-4 py-2 flex justify-between"><span class="font-mono text-xs font-semibold text-slate-900">diet_type_id</span><span>FK ประเภทอาหารหลัก (เช่น ธรรมดา, อ่อน)</span></li>
                <li class="px-4 py-2 flex justify-between"><span class="font-mono text-xs font-semibold text-slate-900">order_type</span><span>ประเภท (normal, manual)</span></li>
                <li class="px-4 py-2 flex justify-between"><span class="font-mono text-xs font-semibold text-slate-900">meal_type</span><span>มื้ออาหาร (breakfast, lunch, dinner, main)</span></li>
                <li class="px-4 py-2 flex justify-between"><span class="font-mono text-xs font-semibold text-slate-900">date</span><span>วันที่ของออเดอร์นี้</span></li>
                <li class="px-4 py-2 flex justify-between"><span class="font-mono text-xs font-semibold text-slate-900">status</span><span>สถานะ (active, cancelled)</span></li>
              </ul>
            </div>

            <!-- Table: users & roles -->
            <div class="border border-slate-200 rounded-lg overflow-hidden shadow-sm">
              <div class="bg-amber-50 px-4 py-3 border-b border-slate-200 font-bold text-amber-900">
                Table: users & roles (ผู้ใช้งานระบบ)
              </div>
              <ul class="divide-y divide-slate-100">
                <li class="px-4 py-2 flex justify-between"><span class="font-mono text-xs font-semibold text-slate-900">users.role_id</span><span>ระดับสิทธิ์การใช้งาน (FK)</span></li>
                <li class="px-4 py-2 flex justify-between"><span class="font-mono text-xs font-semibold text-slate-900">users.approval_status</span><span>สถานะอนุมัติ (pending, active)</span></li>
                <li class="px-4 py-2 flex justify-between"><span class="font-mono text-xs font-semibold text-slate-900">roles.key</span><span>(admin, nurse, nutritionist, kitchen)</span></li>
                <li class="px-4 py-2 flex justify-between"><span class="font-mono text-xs font-semibold text-slate-900">roles.permissions</span><span>JSON กำหนดสิทธิ์เชิงลึก</span></li>
              </ul>
            </div>

            <!-- Table: telegram_notification_schedules -->
            <div class="border border-slate-200 rounded-lg overflow-hidden shadow-sm">
              <div class="bg-sky-50 px-4 py-3 border-b border-slate-200 font-bold text-sky-900">
                Table: telegram_notification_schedules
              </div>
              <ul class="divide-y divide-slate-100">
                <li class="px-4 py-2 flex justify-between"><span class="font-mono text-xs font-semibold text-slate-900">key</span><span>รหัสรอบ (breakfast, lunch, dinner)</span></li>
                <li class="px-4 py-2 flex justify-between"><span class="font-mono text-xs font-semibold text-slate-900">notify_at</span><span>เวลาที่ตั้งให้ส่งแจ้งเตือน (HH:MM:SS)</span></li>
                <li class="px-4 py-2 flex justify-between"><span class="font-mono text-xs font-semibold text-slate-900">enabled</span><span>เปิดใช้งานหรือไม่ (Boolean)</span></li>
                <li class="px-4 py-2 flex justify-between"><span class="font-mono text-xs font-semibold text-slate-900">last_sent_at</span><span>เวลาที่ส่งสำเร็จครั้งล่าสุด</span></li>
              </ul>
            </div>
          </div>
        </article>
      </section>
      </template>
    </div>
    
    <!-- Report Settings Modal -->
    <div v-if="showReportModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 transition-opacity">
      <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col transform transition-all">
        <!-- Header -->
        <div class="bg-indigo-600 px-6 py-4 flex items-center justify-between">
          <h3 class="text-lg font-bold text-white flex items-center gap-2">
            🖨️ พิมพ์รายงานแยกตึก
          </h3>
          <button @click="showReportModal = false" class="text-indigo-200 hover:text-white transition-colors">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        
        <!-- Body -->
        <div class="p-6 space-y-5">
          <!-- Date Selection -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">📅 วันที่รายงาน</label>
            <input type="date" v-model="reportDate" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-slate-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-all" />
          </div>

          <!-- Meal Selection -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">🍽️ มื้ออาหาร</label>
            <div class="grid grid-cols-2 gap-2">
              <button 
                type="button" 
                @click="reportMeal = 'all'" 
                :class="['px-3 py-2 rounded-xl text-sm font-medium transition-all shadow-sm', reportMeal === 'all' ? 'bg-indigo-100 text-indigo-700 border-2 border-indigo-500' : 'bg-slate-50 text-slate-600 border-2 border-transparent hover:bg-slate-100']"
              >ทุกมื้อ</button>
              <button 
                type="button" 
                @click="reportMeal = 'breakfast'" 
                :class="['px-3 py-2 rounded-xl text-sm font-medium transition-all shadow-sm', reportMeal === 'breakfast' ? 'bg-indigo-100 text-indigo-700 border-2 border-indigo-500' : 'bg-slate-50 text-slate-600 border-2 border-transparent hover:bg-slate-100']"
              >มื้อเช้า</button>
              <button 
                type="button" 
                @click="reportMeal = 'lunch'" 
                :class="['px-3 py-2 rounded-xl text-sm font-medium transition-all shadow-sm', reportMeal === 'lunch' ? 'bg-indigo-100 text-indigo-700 border-2 border-indigo-500' : 'bg-slate-50 text-slate-600 border-2 border-transparent hover:bg-slate-100']"
              >มื้อกลางวัน</button>
              <button 
                type="button" 
                @click="reportMeal = 'dinner'" 
                :class="['px-3 py-2 rounded-xl text-sm font-medium transition-all shadow-sm', reportMeal === 'dinner' ? 'bg-indigo-100 text-indigo-700 border-2 border-indigo-500' : 'bg-slate-50 text-slate-600 border-2 border-transparent hover:bg-slate-100']"
              >มื้อเย็น</button>
            </div>
          </div>

          <!-- Preview -->
          <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 text-center">
            <p class="text-sm text-slate-500">พบข้อมูลผู้ป่วยทั้งหมด</p>
            <p class="text-3xl font-bold text-indigo-600 mt-1">{{ reportPatientCount }} <span class="text-lg font-medium text-slate-600">คน</span></p>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex gap-3">
          <button @click="exportExcel" class="flex-1 flex justify-center items-center gap-2 bg-emerald-500 text-white px-4 py-2.5 rounded-xl font-bold hover:bg-emerald-600 shadow-md shadow-emerald-200 transition-all active:scale-95" :disabled="reportPatientCount === 0">
            📊 Export Excel
          </button>
          <button @click="exportPDF" class="flex-1 flex justify-center items-center gap-2 bg-rose-500 text-white px-4 py-2.5 rounded-xl font-bold hover:bg-rose-600 shadow-md shadow-rose-200 transition-all active:scale-95" :disabled="reportPatientCount === 0">
            📄 Export PDF
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import * as XLSX from 'xlsx';
import { THSarabunNew } from '../THSarabunNew.js';
import MetricCard from './MetricCard.vue';
import InfoChip from './InfoChip.vue';
import SummaryRow from './SummaryRow.vue';

const scopes = [
  {
    key: 'nurse',
    labelEn: 'NURSE',
    labelTh: 'พยาบาล',
    title: 'ศูนย์สั่งอาหารพยาบาล',
    description: 'ดูผู้ป่วย เตียง วอร์ด และสถานะอาหารรายคน',
  },
  {
    key: 'nutrition',
    labelEn: 'NUTRITION',
    labelTh: 'โภชนาการ',
    title: 'โต๊ะตรวจความปลอดภัยโภชนาการ',
    description: 'ตรวจ BMI น้ำหนัก โรคประจำตัว แพ้อาหาร และรายการอาหารจาก HIS',
  },
  {
    key: 'kitchen',
    labelEn: 'KITCHEN',
    labelTh: 'ห้องครัว',
    title: 'กระดานผลิตอาหารห้องครัว',
    description: 'ดูรายการอาหารแยกตึก แยกมื้อ และแยกช่วงเวลาเพื่อเตรียมถาดอาหาร',
  },
  {
    key: 'executive',
    labelEn: 'EXECUTIVE',
    labelTh: 'ผู้บริหาร',
    title: 'ภาพรวมสำหรับผู้บริหาร',
    description: 'สรุปสถานะ HIS ภาระงาน และความเสี่ยงโภชนาการ',
  },
  {
    key: 'no_meals',
    labelEn: 'NO MEALS',
    labelTh: 'ไม่มีรายการอาหาร',
    title: 'รายชื่อที่ยังไม่มีรายการอาหาร',
    description: 'ผู้ป่วยในระบบที่ยังไม่ได้ระบุออเดอร์อาหาร',
  },
  {
    key: 'how_to',
    labelEn: 'HOW TO',
    labelTh: 'วิธีใช้งาน',
    title: 'คู่มือวิธีใช้งานระบบ',
    description: 'คำแนะนำและขีดความสามารถของระบบ Hospital Meal System',
  },
  {
    key: 'about',
    labelEn: 'ABOUT',
    labelTh: 'เกี่ยวกับระบบ',
    title: 'เกี่ยวกับระบบ',
    description: 'ข้อมูลผู้พัฒนาระบบและรายละเอียด',
  },
  {
    key: 'data_dict',
    labelEn: 'DATA DICT',
    labelTh: 'ข้อมูลฐานข้อมูล',
    title: 'Data Dictionary',
    description: 'พจนานุกรมข้อมูล (แสดงเฉพาะ Admin)',
  },
];

const mainScopes = computed(() => scopes.filter(s => ['nurse', 'nutrition', 'kitchen', 'executive'].includes(s.key)));

const mealDefinitions = [
  { key: 'breakfast', label: 'เช้า' },
  { key: 'lunch', label: 'กลางวัน' },
  { key: 'dinner', label: 'เย็น' },
  { key: 'main', label: 'อาหารหลัก/รอบอื่น' },
];

const datePresetOptions = [
  { key: 'custom', label: 'กำหนดเอง' },
  { key: 'today', label: 'วันนี้' },
  { key: 'week', label: 'อาทิตย์นี้' },
  { key: 'month', label: 'เดือนนี้' },
];

const activeScope = ref('dashboard');
const activeMealFilter = ref('breakfast');
const activeDatePreset = ref('today');
const customDateStart = ref('');
const customDateEnd = ref('');
const searchTerm = ref('');
const dashboard = ref(null);
const patients = ref([]);
const isLoading = ref(false);
const isSyncing = ref(false);
const isAuthLoading = ref(false);
const errorMessage = ref('');
const syncMessage = ref('');
const quickActionMessage = ref('');
const authMessage = ref('');
const currentUser = ref(null);
const authMode = ref('login');
const roles = ref([]);
const users = ref([]);
const userMessage = ref('');
const dietTypes = ref([]);
const dietTemplates = ref([]);
const isTemplateSaving = ref(false);
const templateMessage = ref('');
const templateForm = ref({
  code: '',
  name: '',
  diet_type_id: '',
  description: '',
  meal_scope: ['breakfast', 'lunch', 'dinner'],
  items: [
    { item_type: 'main', item_name: '', quantity: '1 ที่', notes: '' },
  ],
});
const isManualOrdering = ref(false);
const manualOrderMessage = ref('');
const manualSkippedPatients = ref([]);
const manualOrderMode = ref('beds');
const manualOrderAllMeals = ref(false);
const manualOrderForm = ref({
  service_date: toDisplayDateValue(new Date()),
  template_id: '',
  meal_types: [],
  patient_ids: [],
});
const telegramSchedules = ref([]);
const telegramConfig = ref({
  enabled: false,
  has_bot_token: false,
  has_chat_id: false,
  bot_token: '',
  chat_id: '',
});
const telegramMessage = ref('');
const isTelegramLoading = ref(false);
const loginForm = ref({
  email: '',
  password: '',
});
const showReportModal = ref(false);
const reportDate = ref(toDateInputValue(new Date()));
const reportMeal = ref('all');
const registerForm = ref({
  first_name: '',
  last_name: '',
  position: '',
  phone: '',
  username: '',
  password: '',
  password_confirmation: '',
});
const userForm = ref({
  id: null,
  name: '',
  email: '',
  username: '',
  position: '',
  phone: '',
  password: '',
  role_id: '',
  approval_status: 'active',
});

const currentScope = computed(() => scopes.find((scope) => scope.key === activeScope.value) ?? scopes[0]);
const isMainDashboardScope = computed(() => !['no_meals', 'how_to', 'about', 'data_dict'].includes(activeScope.value));
const currentDateLabel = computed(() => new Intl.DateTimeFormat('th-TH', { dateStyle: 'full' }).format(new Date()));
const cutoffText = computed(() => '05:00');
const syncHealth = computed(() => dashboard.value?.sync_health ?? {});
const hisStatusLabel = computed(() => (syncHealth.value.last_sync ? 'เชื่อมต่อแล้ว' : 'รอซิงก์'));
const dashboardTotals = computed(() => ({
  wards: dashboard.value?.wards ?? dashboard.value?.summary?.by_ward?.length ?? 0,
  trayQueue: dashboard.value?.tray_queue ?? dashboard.value?.summary?.tray_queue ?? 0,
}));
const canManageUsers = computed(() => currentUser.value?.role?.key === 'admin');
const canSyncHis = computed(() => ['admin', 'nutritionist'].includes(currentUser.value?.role?.key));
const canManualOrder = computed(() => ['admin', 'nurse'].includes(currentUser.value?.role?.key));
const canManageTemplates = computed(() => ['admin', 'nurse'].includes(currentUser.value?.role?.key));
const telegramStatusLabel = computed(() => {
  if (!telegramConfig.value.enabled) return 'ปิดใช้งาน';
  if (!telegramConfig.value.has_bot_token || !telegramConfig.value.has_chat_id) return 'ยังตั้งค่า bot/chat ไม่ครบ';
  return 'พร้อมส่ง';
});
const manualMealOptions = computed(() => [
  { key: 'breakfast', label: 'เช้า' },
  { key: 'lunch', label: 'กลางวัน' },
  { key: 'dinner', label: 'เย็น' },
]);

const activeDateRange = computed(() => {
  const today = startOfDay(new Date());

  if (activeDatePreset.value === 'custom') {
    return {
      start: parseDateInput(customDateStart.value),
      end: parseDateInput(customDateEnd.value),
    };
  }

  if (activeDatePreset.value === 'week') {
    const start = startOfDay(new Date(today));
    const day = start.getDay();
    const diffToMonday = day === 0 ? -6 : 1 - day;
    start.setDate(start.getDate() + diffToMonday);
    const end = new Date(start);
    end.setDate(start.getDate() + 6);
    return { start, end };
  }

  if (activeDatePreset.value === 'month') {
    return {
      start: new Date(today.getFullYear(), today.getMonth(), 1),
      end: new Date(today.getFullYear(), today.getMonth() + 1, 0),
    };
  }

  return { start: today, end: today };
});

const dateRangeLabel = computed(() => {
  const { start, end } = activeDateRange.value;
  if (activeDatePreset.value === 'custom' && !start && !end) return 'ทุกวันที่มีรายการอาหาร';
  if (start && !end) return `ตั้งแต่ ${formatDateOnly(start)}`;
  if (!start && end) return `ถึง ${formatDateOnly(end)}`;
  if (!start || !end) return 'กรุณาเลือกวันที่เริ่มต้นและวันที่สิ้นสุด';
  return `${formatDateOnly(start)} ถึง ${formatDateOnly(end)}`;
});

const availableMealDates = computed(() => {
  const timestamps = patients.value
    .flatMap((patient) => patient.meal_entries.map((meal) => meal.date))
    .filter(Boolean)
    .map((date) => parseDateInput(date))
    .filter(Boolean)
    .map((date) => startOfDay(date).getTime());

  return [...new Set(timestamps)].sort((a, b) => a - b).map((timestamp) => new Date(timestamp));
});

const availableDateRange = computed(() => {
  if (availableMealDates.value.length === 0) {
    return { start: null, end: null };
  }

  return {
    start: availableMealDates.value[0],
    end: availableMealDates.value[availableMealDates.value.length - 1],
  };
});

const availableDateRangeLabel = computed(() => {
  const { start, end } = availableDateRange.value;
  if (!start || !end) return 'ยังไม่พบวันที่รายการอาหารจาก HIS';
  return `ข้อมูล HIS ที่พบ: ${formatDateOnly(start)} ถึง ${formatDateOnly(end)}`;
});

const summaryCounts = computed(() => ({
  patients: dashboard.value?.patients ?? patients.value.length ?? 0,
  pendingOrders: dashboard.value?.pending_orders ?? dashboard.value?.summary?.by_status?.draft ?? 0,
  npo: dashboard.value?.npo_count ?? dashboard.value?.summary?.npo_orders ?? 0,
  stat: dashboard.value?.summary?.stat_orders ?? 0,
  highRisk: dashboard.value?.high_risk_patients ?? patients.value.filter((patient) => patient.bmi_warning).length,
}));

const filteredPatients = computed(() => {
  const term = searchTerm.value.trim().toLowerCase();

  return patients.value.filter((patient) => {
    const matchesMeal = activeMealFilter.value === 'all'
      ? hasAnyMealInDateRange(patient)
      : hasMeal(patient, activeMealFilter.value);
    if (!matchesMeal) return false;

    if (!term) return true;

    const haystack = [
      patient.full_name,
      patient.hn,
      patient.an,
      patient.bed_label,
      patient.ward_name,
      patient.room_name,
      patient.diet_order,
      patient.therapeutic_diet,
      patient.chronic_diseases,
      ...patient.allergies,
      ...patient.meal_entries.map((meal) => meal.value),
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase();

    return haystack.includes(term);
  });
});

const groupedPatients = computed(() => {
  const groups = [
    {
      key: 'male',
      label: 'ตึกผู้ป่วยในชาย',
      caption: 'พื้นที่สีฟ้า',
      wrapperClass: 'overflow-hidden rounded-lg border border-sky-200 bg-sky-50/85 shadow-sm',
      patients: [],
    },
    {
      key: 'female',
      label: 'ตึกผู้ป่วยในหญิง',
      caption: 'พื้นที่สีชมพู',
      wrapperClass: 'overflow-hidden rounded-lg border border-rose-200 bg-rose-50/85 shadow-sm',
      patients: [],
    },
  ];

  filteredPatients.value.forEach((patient) => {
    groups.find((group) => group.key === patient.ward_group)?.patients.push(patient);
  });

  return groups.filter((group) => group.patients.length > 0);
});

const reportFilteredPatients = computed(() => {
  return patients.value.filter((patient) => {
    let dateMatches = true;
    let targetDate = null;
    
    if (reportDate.value) {
      targetDate = startOfDay(parseDateInput(reportDate.value)).getTime();
      dateMatches = patient.meal_entries.some(m => {
        if (!m.date) return false;
        return startOfDay(parseDateInput(m.date)).getTime() === targetDate;
      });
    }

    if (!dateMatches) return false;

    if (reportMeal.value !== 'all') {
      let hasTargetMeal = false;
      if (reportDate.value) {
        hasTargetMeal = patient.meal_entries.some(m => {
          if (!m.date) return false;
          const isSameDate = startOfDay(parseDateInput(m.date)).getTime() === targetDate;
          return isSameDate && m.key === reportMeal.value;
        });
      } else {
        hasTargetMeal = hasMeal(patient, reportMeal.value);
      }
      if (!hasTargetMeal) return false;
    }

    return true;
  });
});

const reportGroupedPatients = computed(() => {
  const groups = [
    { key: 'male', label: 'ตึกผู้ป่วยในชาย', patients: [] },
    { key: 'female', label: 'ตึกผู้ป่วยในหญิง', patients: [] },
  ];

  reportFilteredPatients.value.forEach((patient) => {
    groups.find((group) => group.key === patient.ward_group)?.patients.push(patient);
  });

  return groups.filter((group) => group.patients.length > 0);
});

const reportPatientCount = computed(() => reportFilteredPatients.value.length);

const patientsWithoutMeals = computed(() => {
  return patients.value.filter(patient => !hasAnyMealInDateRange(patient));
});

const wardSummary = computed(() => ({
  male: patients.value.filter((patient) => patient.ward_group === 'male').length,
  female: patients.value.filter((patient) => patient.ward_group === 'female').length,
}));

const mealSummary = computed(() => {
  const count = (key) => patients.value.filter((patient) => hasMeal(patient, key)).length;

  return {
    all: patients.value.length,
    breakfast: count('breakfast'),
    lunch: count('lunch'),
    dinner: count('dinner'),
    main: count('main'),
    totalPatientsWithMeals: patients.value.filter(hasAnyMealInDateRange).length,
  };
});

const mealFilterOptions = computed(() => [
  { key: 'all', label: 'ทุกมื้อ/ทุกรอบ', count: mealSummary.value.totalPatientsWithMeals },
  { key: 'breakfast', label: 'เช้า', count: mealSummary.value.breakfast },
  { key: 'lunch', label: 'กลางวัน', count: mealSummary.value.lunch },
  { key: 'dinner', label: 'เย็น', count: mealSummary.value.dinner },
  { key: 'main', label: 'อาหารหลัก/รอบอื่น', count: mealSummary.value.main },
]);
const manualSelectablePatients = computed(() => filteredPatients.value.map((patient) => ({
  ...patient,
  meal_blocked: isMealBlocked(patient),
  meal_block_reason: mealBlockReason(patient),
})));
const groupedManualSelectablePatients = computed(() => {
  const male = manualSelectablePatients.value.filter(p => p.ward_group === 'male');
  const female = manualSelectablePatients.value.filter(p => p.ward_group === 'female');
  
  return [
    { key: 'male', label: 'ตึกผู้ป่วยในชาย', patients: male },
    { key: 'female', label: 'ตึกผู้ป่วยในหญิง', patients: female },
  ].filter(group => group.patients.length > 0);
});
const manualTargetPatientIds = computed(() => {
  if (manualOrderMode.value === 'ward') {
    return manualSelectablePatients.value
      .filter((patient) => !patient.meal_blocked)
      .map((patient) => patient.id);
  }

  return manualOrderForm.value.patient_ids;
});

async function loadDashboard() {
  if (!currentUser.value) return;

  isLoading.value = true;
  errorMessage.value = '';

  try {
    const [dashboardResponse, patientResponse] = await Promise.all([
      axios.get(`/api/v1/dashboard/${activeScope.value}`),
      axios.get('/api/v1/patients?per_page=200'),
    ]);

    dashboard.value = dashboardResponse.data;
    patients.value = (patientResponse.data.data ?? []).map(mapPatient);
    applyAvailableDateRangeWhenEmpty();
    if (manualOrderMode.value === 'ward') {
      syncManualWardSelection();
    }
  } catch (error) {
    errorMessage.value = 'โหลดข้อมูลไม่สำเร็จ กรุณาตรวจสอบ server และฐานข้อมูล';
    console.error(error);
  } finally {
    isLoading.value = false;
  }
}

async function loadDietTemplates() {
  if (!canManualOrder.value) return;

  try {
    const response = await axios.get('/api/v1/diet-templates');
    dietTemplates.value = response.data.filter((template) => template.is_active !== false);
  } catch (error) {
    manualOrderMessage.value = error.response?.data?.message ?? 'โหลดเทมเพลตอาหารไม่สำเร็จ';
  }
}

async function loadDietTemplateReferences() {
  if (!canManualOrder.value) return;

  try {
    const [typesResponse, templatesResponse] = await Promise.all([
      axios.get('/api/v1/diet-types'),
      axios.get('/api/v1/diet-templates'),
    ]);
    dietTypes.value = typesResponse.data;
    dietTemplates.value = templatesResponse.data.filter((template) => template.is_active !== false);

    if (!templateForm.value.diet_type_id && dietTypes.value.length > 0) {
      templateForm.value.diet_type_id = dietTypes.value[0].id;
    }
  } catch (error) {
    const message = error.response?.data?.message ?? 'โหลดประเภทอาหาร/เทมเพลตไม่สำเร็จ';
    manualOrderMessage.value = message;
    templateMessage.value = message;
  }
}

async function saveDietTemplate() {
  if (!canManageTemplates.value) return;

  isTemplateSaving.value = true;
  templateMessage.value = '';

  try {
    const payload = {
      ...templateForm.value,
      is_active: true,
      items: templateForm.value.items.filter((item) => item.item_name.trim() !== ''),
    };

    if (!payload.code || !payload.name || !payload.diet_type_id || payload.items.length === 0) {
      templateMessage.value = 'กรุณากรอกรหัส ชื่อ ประเภทอาหาร และรายการอาหารอย่างน้อย 1 รายการ';
      return;
    }

    const response = await axios.post('/api/v1/diet-templates', payload);
    dietTemplates.value = [...dietTemplates.value, response.data].sort((a, b) => a.name.localeCompare(b.name, 'th'));
    manualOrderForm.value.template_id = response.data.id;
    templateMessage.value = `สร้างเทมเพลต "${response.data.name}" เรียบร้อย และเลือกไว้ใน Manual Order แล้ว`;
    resetTemplateForm();
  } catch (error) {
    templateMessage.value = error.response?.data?.message ?? 'สร้างเทมเพลตไม่สำเร็จ';
  } finally {
    isTemplateSaving.value = false;
  }
}

function addTemplateItem() {
  templateForm.value.items.push({ item_type: 'main', item_name: '', quantity: '1 ที่', notes: '' });
}

function removeTemplateItem(index) {
  if (templateForm.value.items.length === 1) return;

  templateForm.value.items.splice(index, 1);
}

function resetTemplateForm() {
  templateForm.value = {
    code: '',
    name: '',
    diet_type_id: dietTypes.value[0]?.id ?? '',
    description: '',
    meal_scope: ['breakfast', 'lunch', 'dinner'],
    items: [
      { item_type: 'main', item_name: '', quantity: '1 ที่', notes: '' },
    ],
  };
}

async function submitManualOrder() {
  if (!canManualOrder.value) return;

  isManualOrdering.value = true;
  manualOrderMessage.value = '';
  manualSkippedPatients.value = [];

  try {
    const patientIds = manualTargetPatientIds.value;
    if (!manualOrderForm.value.template_id || manualOrderForm.value.meal_types.length === 0 || patientIds.length === 0) {
      manualOrderMessage.value = 'กรุณาเลือกเทมเพลต มื้ออาหาร และเตียงที่สั่งได้อย่างน้อย 1 เตียง';
      return;
    }

    const response = await axios.post('/api/v1/meal-orders/manual-apply', {
      template_id: manualOrderForm.value.template_id,
      service_date: toDateInputValue(parseDateInput(manualOrderForm.value.service_date)),
      meal_types: manualOrderForm.value.meal_types,
      patient_ids: patientIds,
    });

    manualSkippedPatients.value = response.data.skipped_patients ?? [];
    manualOrderMessage.value = `บันทึกคำสั่งอาหาร manual สำเร็จ ${response.data.created} รายการ ข้าม ${response.data.skipped} รายการ`;
    manualOrderForm.value.patient_ids = [];
    await loadDashboard();
  } catch (error) {
    manualOrderMessage.value = error.response?.data?.message ?? 'บันทึกคำสั่งอาหาร manual ไม่สำเร็จ';
  } finally {
    isManualOrdering.value = false;
  }
}

async function syncHis() {
  if (!canSyncHis.value) {
    errorMessage.value = 'role นี้ไม่มีสิทธิ์ซิงก์ข้อมูล HIS';
    return;
  }

  isSyncing.value = true;
  errorMessage.value = '';
  syncMessage.value = 'กำลังซิงก์ข้อมูลผู้ป่วยและรายการอาหารจาก HIS...';

  try {
    const response = await axios.post('/api/v1/sync/hosxp');
    syncMessage.value = response.data.message ?? 'ซิงก์ข้อมูล HIS สำเร็จ';

    if (!response.data.ok) {
      errorMessage.value = syncMessage.value;
    }

    await loadDashboard();
  } catch (error) {
    errorMessage.value = error.response?.data?.message ?? 'ซิงก์ข้อมูล HIS ไม่สำเร็จ กรุณาตรวจสอบการเชื่อมต่อฐาน HIS';
    syncMessage.value = '';
    console.error(error);
  } finally {
    isSyncing.value = false;
  }
}

async function loadCurrentUser() {
  isAuthLoading.value = true;
  authMessage.value = '';

  try {
    const response = await axios.get('/auth/me');
    currentUser.value = response.data.user;

    if (currentUser.value) {
      await loadDashboard();
      if (canManualOrder.value) {
        await loadDietTemplateReferences();
      }
      if (canManageUsers.value) {
        await loadUserManagement();
        await loadTelegramSchedules();
      }
    }
  } catch (error) {
    authMessage.value = 'ตรวจสอบสถานะเข้าสู่ระบบไม่สำเร็จ';
    console.error(error);
  } finally {
    isAuthLoading.value = false;
  }
}

async function login() {
  isAuthLoading.value = true;
  authMessage.value = '';

  try {
    const response = await axios.post('/auth/login', loginForm.value);
    updateCsrfToken(response.data.csrf_token);
    currentUser.value = response.data.user;
    await loadDashboard();

    if (canManualOrder.value) {
      await loadDietTemplateReferences();
    }

    if (canManageUsers.value) {
      await loadUserManagement();
      await loadTelegramSchedules();
    }
  } catch (error) {
    authMessage.value = error.response?.data?.message ?? 'เข้าสู่ระบบไม่สำเร็จ';
  } finally {
    isAuthLoading.value = false;
  }
}

function toggleAuthMode() {
  authMode.value = authMode.value === 'login' ? 'register' : 'login';
  authMessage.value = '';
}

async function registerUser() {
  isAuthLoading.value = true;
  authMessage.value = '';

  try {
    const response = await axios.post('/auth/register', registerForm.value);
    authMessage.value = response.data.message ?? 'ลงทะเบียนสำเร็จ กรุณารอแอดมินอนุมัติบัญชี';
    resetRegisterForm();
    authMode.value = 'login';
  } catch (error) {
    authMessage.value = error.response?.data?.message ?? 'ลงทะเบียนไม่สำเร็จ';
  } finally {
    isAuthLoading.value = false;
  }
}

function resetRegisterForm() {
  registerForm.value = {
    first_name: '',
    last_name: '',
    position: '',
    phone: '',
    username: '',
    password: '',
    password_confirmation: '',
  };
}

async function logout() {
  isAuthLoading.value = true;

  try {
    const response = await axios.post('/auth/logout');
    updateCsrfToken(response.data.csrf_token);
  } finally {
    currentUser.value = null;
    dashboard.value = null;
    patients.value = [];
    users.value = [];
    roles.value = [];
    dietTypes.value = [];
    dietTemplates.value = [];
    templateMessage.value = '';
    resetTemplateForm();
    manualOrderForm.value.patient_ids = [];
    manualOrderMessage.value = '';
    manualSkippedPatients.value = [];
    telegramSchedules.value = [];
    telegramMessage.value = '';
    isAuthLoading.value = false;
  }
}

function updateCsrfToken(token) {
  if (!token) return;

  axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
  document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', token);
}

async function loadUserManagement() {
  if (!canManageUsers.value) return;

  const [rolesResponse, usersResponse] = await Promise.all([
    axios.get('/auth/roles'),
    axios.get('/auth/users'),
  ]);

  roles.value = rolesResponse.data;
  users.value = usersResponse.data;
}

async function loadTelegramSchedules() {
  if (!canManageUsers.value) return;

  isTelegramLoading.value = true;
  telegramMessage.value = '';

  try {
    const response = await axios.get('/auth/telegram/schedules');
    telegramConfig.value = {
      enabled: response.data.enabled,
      has_bot_token: response.data.has_bot_token,
      has_chat_id: response.data.has_chat_id,
      bot_token: response.data.bot_token ?? '',
      chat_id: response.data.chat_id ?? '',
    };
    telegramSchedules.value = (response.data.schedules ?? []).map((schedule) => ({
      key: schedule.key,
      label: schedule.label,
      notify_at: String(schedule.notify_at ?? '').slice(0, 5),
      enabled: Boolean(schedule.enabled),
    }));
  } catch (error) {
    telegramMessage.value = error.response?.data?.message ?? 'โหลดการตั้งค่า Telegram ไม่สำเร็จ';
  } finally {
    isTelegramLoading.value = false;
  }
}

async function saveTelegramConfig() {
  if (!canManageUsers.value) return;

  isTelegramLoading.value = true;
  telegramMessage.value = '';

  try {
    const response = await axios.put('/auth/telegram/config', {
      enabled: telegramConfig.value.enabled,
      bot_token: telegramConfig.value.bot_token,
      chat_id: telegramConfig.value.chat_id,
    });
    telegramMessage.value = response.data.message ?? 'บันทึกการตั้งค่าระบบ Telegram เรียบร้อย';
    await loadTelegramSchedules();
  } catch (error) {
    telegramMessage.value = error.response?.data?.message ?? 'บันทึกการตั้งค่าระบบ Telegram ไม่สำเร็จ';
  } finally {
    isTelegramLoading.value = false;
  }
}

async function saveTelegramSchedules() {
  if (!canManageUsers.value) return;

  isTelegramLoading.value = true;
  telegramMessage.value = '';

  try {
    const response = await axios.put('/auth/telegram/schedules', {
      schedules: telegramSchedules.value.map((schedule) => ({
        key: schedule.key,
        notify_at: schedule.notify_at,
        enabled: schedule.enabled,
      })),
    });

    telegramSchedules.value = (response.data.schedules ?? []).map((schedule) => ({
      key: schedule.key,
      label: schedule.label,
      notify_at: String(schedule.notify_at ?? '').slice(0, 5),
      enabled: Boolean(schedule.enabled),
    }));
    telegramMessage.value = 'บันทึกเวลาแจ้งเตือน Telegram เรียบร้อย';
  } catch (error) {
    telegramMessage.value = error.response?.data?.message ?? 'บันทึกเวลาแจ้งเตือน Telegram ไม่สำเร็จ';
  } finally {
    isTelegramLoading.value = false;
  }
}

async function testTelegramSchedule(key) {
  if (!canManageUsers.value) return;

  isTelegramLoading.value = true;
  telegramMessage.value = '';

  try {
    const response = await axios.post('/auth/telegram/schedules/test', { key });
    telegramMessage.value = response.data.skipped
      ? 'ทดสอบแล้ว แต่ Telegram ยังปิดอยู่หรือตั้งค่า bot/chat ไม่ครบ'
      : 'ส่งข้อความทดสอบ Telegram แล้ว';
  } catch (error) {
    telegramMessage.value = error.response?.data?.message ?? 'ส่งข้อความทดสอบ Telegram ไม่สำเร็จ';
  } finally {
    isTelegramLoading.value = false;
  }
}

async function saveUser() {
  if (!canManageUsers.value) return;

  userMessage.value = '';

  try {
    const payload = { ...userForm.value };
    if (!payload.password) {
      delete payload.password;
    }

    if (payload.id) {
      await axios.put(`/auth/users/${payload.id}`, payload);
      userMessage.value = 'บันทึกผู้ใช้เรียบร้อย';
    } else {
      await axios.post('/auth/users', payload);
      userMessage.value = 'เพิ่มผู้ใช้เรียบร้อย';
    }

    resetUserForm();
    await loadUserManagement();
  } catch (error) {
    userMessage.value = error.response?.data?.message ?? 'บันทึกผู้ใช้ไม่สำเร็จ';
  }
}

function editUser(user) {
  userForm.value = {
    id: user.id,
    name: user.name,
    email: user.email,
    username: user.username ?? '',
    position: user.position ?? '',
    phone: user.phone ?? '',
    password: '',
    role_id: user.role_id,
    approval_status: user.approval_status ?? 'active',
  };
}

function resetUserForm() {
  userForm.value = {
    id: null,
    name: '',
    email: '',
    username: '',
    position: '',
    phone: '',
    password: '',
    role_id: '',
    approval_status: 'active',
  };
}

async function approveUser(user) {
  if (!canManageUsers.value) return;

  userMessage.value = '';

  try {
    await axios.post(`/auth/users/${user.id}/approve`, { role_id: user.role_id });
    userMessage.value = `อนุมัติบัญชี ${user.name} เรียบร้อย`;
    await loadUserManagement();
  } catch (error) {
    userMessage.value = error.response?.data?.message ?? 'อนุมัติผู้ใช้ไม่สำเร็จ';
  }
}

async function rejectUser(user) {
  if (!canManageUsers.value) return;

  userMessage.value = '';

  try {
    await axios.post(`/auth/users/${user.id}/reject`);
    userMessage.value = `ปฏิเสธบัญชี ${user.name} เรียบร้อย`;
    await loadUserManagement();
  } catch (error) {
    userMessage.value = error.response?.data?.message ?? 'ปฏิเสธผู้ใช้ไม่สำเร็จ';
  }
}

function approvalStatusLabel(status) {
  return {
    active: 'อนุมัติแล้ว',
    pending: 'รออนุมัติ',
    rejected: 'ปฏิเสธ',
  }[status] ?? 'ไม่ระบุ';
}

function approvalStatusClass(status) {
  if (status === 'pending') return 'status-pill status-pill-warn';
  if (status === 'rejected') return 'status-pill status-pill-danger';
  return 'status-pill status-pill-soft';
}

function mapPatient(patient) {
  const allergies = patient.allergies?.map((item) => item.allergen) ?? [];
  const { meals, mealEntries } = parseHisMeals(patient.clinical_note);

  return {
    ...patient,
    full_name: [patient.prefix, patient.first_name, patient.last_name].filter(Boolean).join(' '),
    ward_name: patient.ward?.name ?? '-',
    room_name: patient.room?.code ?? '-',
    bed_label: patient.bed?.code ? `เตียง ${patient.bed.code}` : '-',
    ward_group: resolveWardGroup(patient.ward?.name),
    allergies,
    chronic_diseases: patient.secondary_diagnosis,
    therapeutic_diet: patient.therapeutic_diet,
    specific_group_diet: patient.specific_group_diet,
    food_allergy: patient.food_allergy,
    bmi_warning: patient.bmi !== null && (patient.bmi < 18.5 || patient.bmi >= 30),
    meal_blocked: isMealBlocked(patient),
    meal_block_reason: mealBlockReason(patient),
    meals,
    meal_entries: mealEntries,
  };
}

function mealBlockReason(patient) {
  if (patient.npo_status) {
    return 'NPO: งดน้ำ/งดอาหาร';
  }

  const text = [
    patient.diet_order,
    patient.therapeutic_diet,
    patient.clinical_note,
    patient.primary_diagnosis,
    patient.secondary_diagnosis,
  ].filter(Boolean).join(' ').toLowerCase();

  const blockedTerms = [
    { term: 'งดน้ำ', reason: 'แพทย์สั่งงดน้ำ' },
    { term: 'งดนํ้า', reason: 'แพทย์สั่งงดน้ำ' },
    { term: 'งดอาหาร', reason: 'แพทย์สั่งงดอาหาร' },
    { term: 'npo', reason: 'แพทย์สั่งงดอาหาร' },
    { term: 'nil per os', reason: 'แพทย์สั่งงดอาหาร' },
    { term: 'nothing by mouth', reason: 'แพทย์สั่งงดอาหาร' },
    { term: 'no oral', reason: 'แพทย์สั่งงดอาหาร' },
  ];

  return blockedTerms.find((item) => text.includes(item.term))?.reason ?? '';
}

function isMealBlocked(patient) {
  return Boolean(mealBlockReason(patient));
}

function dietOrderClass(diet) {
  if (!diet || diet === '-') return 'border-slate-300 bg-white text-slate-700';
  const normalTerms = ['ธรรมดา', 'ปกติ', 'normal', 'regular'];
  const isNormal = normalTerms.some(term => diet.toLowerCase().includes(term));
  return isNormal ? 'border-slate-300 bg-white text-slate-700' : 'border-rose-200 bg-rose-50 text-rose-700';
}

function syncManualWardSelection() {
  if (manualOrderMode.value !== 'ward') return;

  manualOrderForm.value.patient_ids = manualSelectablePatients.value
    .filter((patient) => !patient.meal_blocked)
    .map((patient) => patient.id);
}

function parseHisMeals(note) {
  const meals = {
    breakfast: '',
    lunch: '',
    dinner: '',
    main: '',
  };
  const mealEntries = [];

  if (!note?.includes('HIS:')) {
    return { meals, mealEntries };
  }

  const segments = note.replace(/^.*?HIS:\s*/, '').split('|').map((segment) => segment.trim());

  segments.forEach((segment) => {
    const [mealLabel, ...detailParts] = segment.split(':');
    const rawDetail = detailParts.join(':').trim();

    if (!rawDetail) return;

    const key = resolveMealKey(mealLabel);
    const label = mealDefinitions.find((meal) => meal.key === key)?.label ?? mealLabel;

    splitDatedMealDetails(rawDetail).forEach(({ date, detail }) => {
      meals[key] = meals[key] ? `${meals[key]}, ${detail}` : detail;
      mealEntries.push({
        key,
        label,
        value: detail,
        date,
      });
    });
  });

  return { meals, mealEntries };
}

function splitDatedMealDetails(rawDetail) {
  const datedMatches = [...rawDetail.matchAll(/\[(\d{4}-\d{2}-\d{2})\]\s*([^[]+)/g)];

  if (datedMatches.length === 0) {
    const detail = rawDetail.trim();
    return detail ? [{ date: '', detail }] : [];
  }

  return datedMatches
    .map((match) => ({
      date: match[1],
      detail: match[2].replace(/,\s*$/, '').trim(),
    }))
    .filter((row) => row.detail);
}

function visibleMeals(patient) {
  const rows = patient.meal_entries
    .filter((meal) => activeMealFilter.value === 'all' || activeMealFilter.value === meal.key)
    .filter((meal) => isDateInActiveRange(meal.date))
    .sort((left, right) => mealOrder(left.key) - mealOrder(right.key))
    .map((meal) => ({
      ...meal,
      label: meal.date ? `${meal.label} (${formatDateOnly(parseDateInput(meal.date))})` : meal.label,
    }));

  if (rows.length > 0) return rows;

  return [{ key: 'none', label: 'รายการอาหาร', value: patient.npo_status ? 'งดอาหาร' : 'ยังไม่มีรายการจาก HIS ในช่วงเวลานี้' }];
}

function hasMeal(patient, key) {
  return patient.meal_entries.some((meal) => meal.key === key && isDateInActiveRange(meal.date));
}

function hasAnyMealInDateRange(patient) {
  return patient.meal_entries.some((meal) => isDateInActiveRange(meal.date));
}

function resolveMealKey(mealLabel) {
  if (mealLabel.includes('เช้า')) return 'breakfast';
  if (mealLabel.includes('กลางวัน') || mealLabel.includes('เที่ยง')) return 'lunch';
  if (mealLabel.includes('เย็น')) return 'dinner';
  return 'main';
}

function mealOrder(key) {
  return {
    breakfast: 1,
    lunch: 2,
    dinner: 3,
    main: 4,
  }[key] ?? 5;
}

function hasClinicalFlags(patient) {
  return Boolean(patient.npo_status || patient.weight_kg || patient.bmi || patient.therapeutic_diet || patient.chronic_diseases || patient.allergies.length);
}

function resolveWardGroup(wardName = '') {
  if (wardName.includes('ชาย')) return 'male';
  if (wardName.includes('หญิง')) return 'female';
  return 'other';
}

function setDatePreset(preset) {
  activeDatePreset.value = preset;
  if (preset !== 'custom') {
    const { start, end } = activeDateRange.value;
    customDateStart.value = toDisplayDateValue(start);
    customDateEnd.value = toDisplayDateValue(end);
  }
}

function useAvailableDateRange() {
  const { start, end } = availableDateRange.value;
  activeDatePreset.value = 'custom';
  customDateStart.value = start ? toDisplayDateValue(start) : '';
  customDateEnd.value = end ? toDisplayDateValue(end) : '';
}

function applyAvailableDateRangeWhenEmpty() {
  if (activeDatePreset.value !== 'custom' || customDateStart.value || customDateEnd.value) {
    return;
  }

  useAvailableDateRange();
}

function resetFilters() {
  searchTerm.value = '';
  activeMealFilter.value = 'all';
  useAvailableDateRange();
}

function performQuickAction(label) {
  quickActionMessage.value = `${label} พร้อมต่อยอดเป็น workflow จริง`;
}

function scopeButtonClass(scope) {
  return [
    'glass-button',
    activeScope.value === scope ? 'glass-button-active' : 'glass-button-idle',
  ];
}

function filterButtonClass(active) {
  return [
    'rounded-lg border px-3 py-2 text-xs font-semibold transition',
    active
      ? 'border-cyan-300 bg-cyan-100 text-cyan-900 shadow-sm'
      : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50',
  ];
}

function formatNumber(value) {
  if (value === null || value === undefined || value === '') return '-';
  return new Intl.NumberFormat('th-TH', { maximumFractionDigits: 2 }).format(Number(value));
}

function formatDateTime(value) {
  if (!value) return '-';
  return new Intl.DateTimeFormat('th-TH', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(value));
}

function startOfDay(date) {
  const next = new Date(date);
  next.setHours(0, 0, 0, 0);
  return next;
}

function parseDateInput(value) {
  if (!value) return null;

  if (value.includes('/')) {
    const [day, month, year] = value.split('/').map(Number);
    if (!year || !month || !day) return null;
    return new Date(year, month - 1, day);
  }

  const [year, month, day] = value.split('-').map(Number);
  if (!year || !month || !day) return null;
  return new Date(year, month - 1, day);
}

function toDateInputValue(date) {
  if (!date) return '';
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function toDisplayDateValue(date) {
  if (!date) return '';
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${day}/${month}/${year}`;
}

function isDateInActiveRange(value) {
  const { start, end } = activeDateRange.value;
  if (!value) return false;
  if (activeDatePreset.value === 'custom' && !start && !end) return true;
  const date = parseDateInput(value);
  if (start && date < startOfDay(start)) return false;
  if (end && date > startOfDay(end)) return false;
  return true;
}

function formatDateOnly(date) {
  if (!date) return '-';
  return new Intl.DateTimeFormat('th-TH-u-ca-buddhist', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  }).format(date);
}

watch(activeScope, loadDashboard);
watch(manualOrderAllMeals, (checked) => {
  manualOrderForm.value.meal_types = checked ? ['breakfast', 'lunch', 'dinner'] : [];
});
watch(manualOrderMode, (mode) => {
  if (mode === 'ward') {
    syncManualWardSelection();
  } else {
    manualOrderForm.value.patient_ids = [];
  }
});
watch(manualSelectablePatients, () => {
  if (manualOrderMode.value === 'ward') {
    syncManualWardSelection();
  }
});

function exportExcel() {
  const wb = XLSX.utils.book_new();
  
  reportGroupedPatients.value.forEach(group => {
    const data = group.patients.map(p => ({
      'หมายเลขเตียง': p.bed_label,
      'ชื่อ-สกุล': p.full_name,
      'HN': p.hn,
      'มื้อ': (p.meal_entries || [])
               .filter(m => reportDate.value ? startOfDay(parseDateInput(m.date)).getTime() === startOfDay(parseDateInput(reportDate.value)).getTime() : true)
               .filter(m => reportMeal.value === 'all' || m.key === reportMeal.value)
               .map(m => m.label).join(', ') || '-',
      'รายการอาหาร': (p.meal_entries || [])
               .filter(m => reportDate.value ? startOfDay(parseDateInput(m.date)).getTime() === startOfDay(parseDateInput(reportDate.value)).getTime() : true)
               .filter(m => reportMeal.value === 'all' || m.key === reportMeal.value)
               .map(m => m.value).join(', ') || '-',
      'อาหารเฉพาะโรค': p.therapeutic_diet || '-',
      'ค่า BMI': p.bmi || '-',
      'สถานะพิเศษ': p.npo_status ? 'NPO' : (p.food_allergy ? 'แพ้: ' + p.food_allergy : '-')
    }));
    
    if (data.length > 0) {
      const ws = XLSX.utils.json_to_sheet(data);
      const safeTitle = group.label.substring(0, 31).replace(/[\\/*?:\[\]]/g, '');
      XLSX.utils.book_append_sheet(wb, ws, safeTitle);
    }
  });

  if (wb.SheetNames.length === 0) {
    alert('ไม่มีข้อมูลสำหรับส่งออก');
    return;
  }
  
  XLSX.writeFile(wb, `รายงานอาหารแยกตึก_${formatDateOnly(new Date())}.xlsx`);
}

function exportPDF() {
  const doc = new jsPDF('l', 'pt', 'a4');
  
  doc.addFileToVFS("THSarabunNew.ttf", THSarabunNew);
  doc.addFont("THSarabunNew.ttf", "THSarabunNew", "normal");
  doc.setFont("THSarabunNew");
  
  doc.setFontSize(22);
  doc.text('รายงานอาหารแยกตึก', 40, 40);
  doc.setFontSize(14);
  const displayDate = reportDate.value ? formatDateOnly(parseDateInput(reportDate.value)) : 'ทั้งหมด';
  doc.text(`ประจำวันที่: ${displayDate}`, 40, 60);

  let startY = 80;

  reportGroupedPatients.value.forEach(group => {
    if (group.patients.length === 0) return;
    
    doc.setFontSize(16);
    doc.text(`ตึก: ${group.label}`, 40, startY);
    
    const tableBody = group.patients.map(p => {
      const filteredMeals = (p.meal_entries || [])
               .filter(m => reportDate.value ? startOfDay(parseDateInput(m.date)).getTime() === startOfDay(parseDateInput(reportDate.value)).getTime() : true)
               .filter(m => reportMeal.value === 'all' || m.key === reportMeal.value);
      
      return [
        p.bed_label,
        p.full_name,
        filteredMeals.map(m => m.label).join(', ') || '-',
        filteredMeals.map(m => m.value).join(', ') || '-',
        p.therapeutic_diet || '-',
        p.bmi || '-',
        p.npo_status ? 'NPO' : (p.food_allergy ? `แพ้: ${p.food_allergy}` : '-')
      ];
    });

    autoTable(doc, {
      startY: startY + 10,
      head: [['หมายเลขเตียง', 'ชื่อ-สกุล', 'มื้อ', 'รายการอาหาร', 'อาหารเฉพาะโรค', 'ค่า BMI', 'สถานะพิเศษ']],
      body: tableBody,
      styles: { font: 'THSarabunNew', fontSize: 12 }, 
      headStyles: { fillColor: [40, 40, 40], fontStyle: 'normal' }
    });

    startY = doc.lastAutoTable.finalY + 30;
    
    if (startY > doc.internal.pageSize.getHeight() - 50) {
      doc.addPage();
      startY = 40;
    }
  });

  doc.save(`Ward_Report_${formatDateOnly(new Date())}.pdf`);
}

onMounted(loadCurrentUser);
</script>
