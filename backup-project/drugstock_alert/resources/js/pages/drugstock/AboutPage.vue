<template>
  <div class="about-page">
    <v-card class="mb-6 premium-hero" rounded="xl" elevation="0">
      <div class="hero-bg-glow"></div>
      <v-card-text class="d-flex flex-column align-center text-center pa-10 relative-z1">
        <v-avatar size="80" color="primary" variant="tonal" class="mb-4 shadow-sm">
          <v-icon icon="mdi-pill" size="48" />
        </v-avatar>
        <div class="text-h3 font-weight-black text-slate-900 mb-4 hero-title">เกี่ยวกับระบบ</div>
        <div class="text-h6 text-slate-600 max-w-3xl mx-auto line-height-relaxed font-weight-regular">
          <strong class="text-primary">Drug Stock Alert System</strong> <br> ระบบบริหารจัดการคลังยาและเวชภัณฑ์อัจฉริยะ 
          มุ่งเน้นการแจ้งเตือนและวิเคราะห์ข้อมูลเพื่อลดความผิดพลาดและเพิ่มประสิทธิภาพในการทำงานของบุคลากรทางการแพทย์
        </div>
      </v-card-text>
    </v-card>

    <v-row dense class="mb-6">
      <v-col cols="12" md="7">
        <v-card rounded="xl" elevation="0" class="h-100 premium-panel">
          <v-card-title class="pa-6 pb-2 text-h5 font-weight-black d-flex align-center gap-3 text-slate-900">
            <div class="icon-box icon-box--indigo"><v-icon icon="mdi-bullseye-arrow" size="24" /></div>
            วัตถุประสงค์และจุดเด่นของระบบ
          </v-card-title>
          <v-card-text class="pa-6 pt-4">
            <div class="premium-list-container">
              <div class="premium-list-item">
                <v-icon icon="mdi-check-circle" color="success" size="24" class="mr-3 mt-1" />
                <div>
                  <div class="font-weight-bold text-subtitle-1 text-slate-800">ติดตามปริมาณคงคลัง (Real-time Stock Tracking)</div>
                  <div class="text-slate-600 text-body-2">รู้ความเคลื่อนไหวของการ รับ-จ่าย ทันทีเพื่อป้องกันปัญหาสินค้าขาดมือในภาวะฉุกเฉิน</div>
                </div>
              </div>
              
              <div class="premium-list-item">
                <v-icon icon="mdi-alert-circle" color="warning" size="24" class="mr-3 mt-1" />
                <div>
                  <div class="font-weight-bold text-subtitle-1 text-slate-800">แจ้งเตือนวันหมดอายุ (Expiry Alert Automation)</div>
                  <div class="text-slate-600 text-body-2">มีระบบเตือนล่วงหน้าสำหรับยาที่ใกล้หมดอายุ ช่วยลดความสูญเสียจากยาเสื่อมสภาพ</div>
                </div>
              </div>

              <div class="premium-list-item">
                <v-icon icon="mdi-chart-bell-curve-cumulative" color="primary" size="24" class="mr-3 mt-1" />
                <div>
                  <div class="font-weight-bold text-subtitle-1 text-slate-800">จุดสั่งซื้ออัจฉริยะ (Reorder Point System)</div>
                  <div class="text-slate-600 text-body-2">วิเคราะห์ปริมาณการใช้และแนะนำจุดสั่งซื้อที่เหมาะสมเพื่อรักษาสมดุลของคลัง</div>
                </div>
              </div>

              <div class="premium-list-item">
                <v-icon icon="mdi-lan" color="teal" size="24" class="mr-3 mt-1" />
                <div>
                  <div class="font-weight-bold text-subtitle-1 text-slate-800">รองรับคลังย่อยไม่จำกัด (Multi-warehouse Support)</div>
                  <div class="text-slate-600 text-body-2">กระจายการจัดการคลังไปยังแผนกต่างๆ ได้อย่างมีประสิทธิภาพ พร้อมระบบ Audit Trail</div>
                </div>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
      
      <v-col cols="12" md="5">
        <v-card rounded="xl" elevation="0" class="h-100 premium-panel bg-slate-50 d-flex flex-column">
          <v-card-title class="pa-6 pb-2 text-h5 font-weight-black d-flex align-center gap-3 text-slate-900">
            <div class="icon-box icon-box--teal"><v-icon icon="mdi-information-variant" size="24" /></div>
            ข้อมูลเวอร์ชัน
          </v-card-title>
          <v-card-text class="pa-6 pt-6 flex-grow-1">
            <div class="version-info-box">
              <div class="info-row">
                <span class="info-label">เวอร์ชันปัจจุบัน</span>
                <v-chip color="success" size="small" variant="flat" class="font-weight-bold px-3">V 1.2.0 (Stable)</v-chip>
              </div>
              <v-divider class="my-3 border-slate-200"></v-divider>
              
              <div class="info-row">
                <span class="info-label">วันที่อัปเดตล่าสุด</span>
                <span class="info-value">15 กรกฎาคม 2026</span>
              </div>
              <v-divider class="my-3 border-slate-200"></v-divider>
              
              <div class="info-row">
                <span class="info-label">ทีมผู้พัฒนา</span>
                <span class="info-value text-primary font-weight-bold">IT Solutions Dept.</span>
              </div>
              <v-divider class="my-3 border-slate-200"></v-divider>

              <div class="info-row">
                <span class="info-label">เฟรมเวิร์ก</span>
                <span class="info-value">Laravel 11 + Vue 3</span>
              </div>
            </div>

            <div class="mt-8 text-center">
              <v-icon icon="mdi-hospital-building" size="48" color="slate-300" class="opacity-30" />
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<style scoped>
/* Color Palette */
.text-slate-900 { color: #0f172a; }
.text-slate-800 { color: #1e293b; }
.text-slate-600 { color: #475569; }
.bg-slate-50 { background-color: #f8fafc; }
.border-slate-200 { border-color: #e2e8f0; }

.about-page {
  display: flex;
  flex-direction: column;
}

/* Premium Hero Section */
.premium-hero {
  position: relative;
  overflow: hidden;
  background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%);
  border: 1px solid rgba(148, 163, 184, 0.2);
  box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.05);
}

.hero-bg-glow {
  position: absolute;
  top: -150px;
  left: 50%;
  transform: translateX(-50%);
  width: 600px;
  height: 600px;
  background: radial-gradient(circle, rgba(59, 130, 246, 0.08) 0%, transparent 60%);
  border-radius: 50%;
  pointer-events: none;
}

.relative-z1 {
  position: relative;
  z-index: 1;
}

.hero-title {
  letter-spacing: -0.02em;
}

.max-w-3xl {
  max-width: 48rem;
}

.line-height-relaxed {
  line-height: 1.7;
}

/* Icons & Panels */
.icon-box {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 42px;
  height: 42px;
  border-radius: 12px;
  color: white;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.icon-box--indigo { background: linear-gradient(135deg, #6366f1, #4f46e5); box-shadow: 0 6px 16px rgba(79, 70, 229, 0.25); }
.icon-box--teal { background: linear-gradient(135deg, #14b8a6, #0d9488); box-shadow: 0 6px 16px rgba(13, 148, 136, 0.25); }

.premium-panel {
  border: 1px solid rgba(148, 163, 184, 0.15);
  box-shadow: 0 16px 32px -12px rgba(15, 23, 42, 0.06);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.premium-panel:hover {
  box-shadow: 0 20px 40px -10px rgba(15, 23, 42, 0.08);
}

/* Custom List */
.premium-list-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
}
.premium-list-item {
  display: flex;
  align-items: flex-start;
  padding: 16px;
  border-radius: 16px;
  background: rgba(248, 250, 252, 0.6);
  border: 1px solid transparent;
  transition: all 0.2s;
}
.premium-list-item:hover {
  background: white;
  border-color: rgba(99, 102, 241, 0.2);
  box-shadow: 0 8px 24px -8px rgba(15, 23, 42, 0.08);
  transform: translateX(4px);
}

/* Info Box */
.version-info-box {
  background: white;
  border-radius: 16px;
  padding: 20px;
  border: 1px solid rgba(226, 232, 240, 0.8);
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
}
.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.info-label {
  color: #64748b;
  font-size: 14px;
  font-weight: 600;
}
.info-value {
  color: #0f172a;
  font-size: 15px;
  font-weight: 700;
}
</style>
