<template>
  <div class="help-page">
    <v-card class="mb-6 premium-hero" rounded="xl" elevation="0">
      <div class="hero-bg-glow"></div>
      <v-card-text class="d-flex align-center gap-6 pa-10 relative-z1 flex-wrap">
        <v-avatar size="80" color="rose" variant="tonal" class="shadow-sm">
          <v-icon icon="mdi-book-open-page-variant" size="40" />
        </v-avatar>
        <div class="hero-text">
          <div class="text-h3 font-weight-black text-slate-900 mb-3 hero-title">วิธีใช้งานระบบ (User Manual)</div>
          <div class="text-h6 text-slate-600 font-weight-regular max-w-3xl line-height-relaxed">
            คู่มือการใช้งานฟังก์ชันต่างๆ ของระบบคลังยาและแจ้งเตือนสต็อก <br>
            เพื่อให้คุณสามารถบริหารจัดการสต็อกได้อย่างมีประสิทธิภาพและรวดเร็วที่สุด
          </div>
        </div>
      </v-card-text>
    </v-card>

    <v-row dense>
      <v-col cols="12" lg="10" class="mx-auto">
        <div class="text-h6 font-weight-black mb-4 text-slate-800 px-1 d-flex align-center gap-2 mt-4">
          <v-icon icon="mdi-help-network-outline" color="primary" /> หัวข้อการใช้งานหลัก
        </div>

        <v-expansion-panels variant="accordion" class="premium-panels mb-8" v-model="activePanel">
          
          <v-expansion-panel class="mb-4 rounded-xl border-slate" value="dashboard">
            <v-expansion-panel-title class="text-h6 font-weight-bold pa-6 panel-title">
              <v-avatar size="36" color="blue-lighten-4" class="mr-4 text-blue-darken-2 font-weight-black">1</v-avatar> 
              การดูภาพรวมและการแจ้งเตือน (Dashboard & Alerts)
            </v-expansion-panel-title>
            <v-expansion-panel-text class="pa-4 pt-0 text-slate-600 text-body-1 line-height-relaxed">
              <p class="mb-3">
                หน้า <strong>Dashboard</strong> จะทำหน้าที่เป็นศูนย์กลางรวบรวมข้อมูลสถานะของคลังยาแบบ Real-time โดยแสดง:
              </p>
              <ul class="ml-6 mb-4">
                <li class="mb-2"><strong>KPIs สำคัญ:</strong> สรุปยอดคลังทั้งหมด, ยาที่ใกล้หมดอายุ, ยาที่หมดอายุแล้ว และยอดรับจ่ายประจำวัน</li>
                <li class="mb-2"><strong>สรุปการแจ้งเตือน 4 ระยะ:</strong> เพื่อให้รู้ได้ทันทีว่ามียาตัวใดที่อยู่ในระยะวิกฤต (หมดอายุ/สินค้าขาด) หรือ ระยะจุดสั่งซื้อ (Reorder)</li>
                <li class="mb-2"><strong>แนวโน้มย้อนหลัง 30 วัน:</strong> ช่วยให้เห็นภาพรวมของเหตุการณ์การแจ้งเตือนที่เกิดขึ้นในอดีต</li>
              </ul>
              <v-alert type="info" variant="tonal" class="mt-4 rounded-lg">
                คุณสามารถคลิกที่ปุ่ม <strong>"ดูการแจ้งเตือนทั้งหมด"</strong> เพื่อเข้าไปจัดการปัญหา เช่น กดปุ่ม <em>รับทราบ (Acknowledge)</em> หรือ <em>แก้ไขแล้ว (Resolve)</em> หลังจากได้ทำการเติมสต็อกหรือตัดยาที่หมดอายุทิ้งไปแล้ว
              </v-alert>
            </v-expansion-panel-text>
          </v-expansion-panel>

          <v-expansion-panel class="mb-4 rounded-xl border-slate" value="warehouses">
            <v-expansion-panel-title class="text-h6 font-weight-bold pa-6 panel-title">
              <v-avatar size="36" color="green-lighten-4" class="mr-4 text-green-darken-2 font-weight-black">2</v-avatar> 
              การจัดการคลังย่อย (Warehouses Management)
            </v-expansion-panel-title>
            <v-expansion-panel-text class="pa-4 pt-0 text-slate-600 text-body-1 line-height-relaxed">
              <p class="mb-3">
                ไปที่เมนู <strong>คลังย่อย</strong> เพื่อดูรายชื่อคลังทั้งหมดในระบบ เช่น คลังห้องฉุกเฉิน, คลังผู้ป่วยใน เป็นต้น
              </p>
              <ul class="ml-6 mb-4">
                <li class="mb-2"><strong>การเพิ่มคลังใหม่:</strong> กดปุ่ม <code>+ เพิ่มคลังย่อย</code> ระบบจะให้กรอกชื่อคลัง, รหัส และที่ตั้ง</li>
                <li class="mb-2"><strong>การดูรายละเอียดคลัง:</strong> คลิกที่ชื่อคลังเพื่อดูรายการยาที่มีอยู่เฉพาะในคลังนั้น รวมถึงประวัติการเบิก-จ่าย</li>
              </ul>
            </v-expansion-panel-text>
          </v-expansion-panel>

          <v-expansion-panel class="mb-4 rounded-xl border-slate" value="drugs">
            <v-expansion-panel-title class="text-h6 font-weight-bold pa-6 panel-title">
              <v-avatar size="36" color="amber-lighten-4" class="mr-4 text-amber-darken-4 font-weight-black">3</v-avatar> 
              การจัดการรายการยาและเวชภัณฑ์ (Drugs Database)
            </v-expansion-panel-title>
            <v-expansion-panel-text class="pa-4 pt-0 text-slate-600 text-body-1 line-height-relaxed">
              <p class="mb-3">
                ระบบอนุญาตให้คุณเพิ่ม แก้ไข หรือลบรายการยาได้ที่เมนู <strong>ยา</strong> (Drugs) โดยข้อมูลสำคัญที่ต้องระบุได้แก่:
              </p>
              <ul class="ml-6 mb-4">
                <li class="mb-2"><strong>รหัสยา (SKU) และ ชื่อยา:</strong> เพื่อใช้ในการอ้างอิงและค้นหา</li>
                <li class="mb-2"><strong>จุดสั่งซื้อ (Reorder Point):</strong> คือจำนวนขั้นต่ำ หากสต็อกในคลังลดลงจนถึงตัวเลขนี้ ระบบจะสร้างการแจ้งเตือนทันทีเพื่อให้เริ่มกระบวนการจัดซื้อ</li>
                <li class="mb-2"><strong>หน่วยนับ:</strong> เช่น แผง, กล่อง, เม็ด, ขวด</li>
              </ul>
            </v-expansion-panel-text>
          </v-expansion-panel>

          <v-expansion-panel class="mb-4 rounded-xl border-slate" value="movements">
            <v-expansion-panel-title class="text-h6 font-weight-bold pa-6 panel-title">
              <v-avatar size="36" color="purple-lighten-4" class="mr-4 text-purple-darken-2 font-weight-black">4</v-avatar> 
              การบันทึกการเบิก-จ่าย (Stock Movements)
            </v-expansion-panel-title>
            <v-expansion-panel-text class="pa-4 pt-0 text-slate-600 text-body-1 line-height-relaxed">
              <p class="mb-3">
                ระบบรองรับการบันทึกความเคลื่อนไหวของสต็อกแบบอัตโนมัติเมื่อทำรายการดังนี้:
              </p>
              <v-row class="mt-2 mb-2">
                <v-col cols="12" sm="4">
                  <div class="movement-card bg-green-50">
                    <v-icon icon="mdi-arrow-down-bold" color="success" class="mb-2" size="32" />
                    <div class="font-weight-bold text-success">รับเข้า (Receive)</div>
                    <div class="text-caption mt-1">การนำของเข้าคลัง พร้อมระบุเลขที่ Lot และวันหมดอายุ</div>
                  </div>
                </v-col>
                <v-col cols="12" sm="4">
                  <div class="movement-card bg-orange-50">
                    <v-icon icon="mdi-arrow-up-bold" color="warning" class="mb-2" size="32" />
                    <div class="font-weight-bold text-warning">เบิก/จ่าย (Issue)</div>
                    <div class="text-caption mt-1">การนำของออกไปใช้ สต็อกจะถูกตัดออกทันที</div>
                  </div>
                </v-col>
                <v-col cols="12" sm="4">
                  <div class="movement-card bg-blue-50">
                    <v-icon icon="mdi-scissors-cutting" color="primary" class="mb-2" size="32" />
                    <div class="font-weight-bold text-primary">ปรับปรุงยอด (Adjust)</div>
                    <div class="text-caption mt-1">ใช้กรณีของสูญหาย เสียหาย หรือสต็อกไม่ตรงกับระบบ</div>
                  </div>
                </v-col>
              </v-row>
            </v-expansion-panel-text>
          </v-expansion-panel>

          <v-expansion-panel class="mb-4 rounded-xl border-slate" value="alerts">
            <v-expansion-panel-title class="text-h6 font-weight-bold pa-6 panel-title">
              <v-avatar size="36" color="red-lighten-4" class="mr-4 text-red-darken-2 font-weight-black">5</v-avatar> 
              ระบบการแจ้งเตือนอัตโนมัติ (Alert System)
            </v-expansion-panel-title>
            <v-expansion-panel-text class="pa-4 pt-0 text-slate-600 text-body-1 line-height-relaxed">
              <p class="mb-3">
                ระบบจะทำหน้าที่เฝ้าระวังและแจ้งเตือนคุณแบบอัตโนมัติ โดยแบ่งเงื่อนไขออกเป็น 3 กรณีหลัก:
              </p>
              <ul class="ml-6 mb-4">
                <li class="mb-2"><strong>ยาใกล้หมดอายุ (4 ระยะ):</strong> แจ้งเตือนล่วงหน้าเมื่อยาใกล้หมดอายุตามรอบ 90, 60, 45 และ 30 วัน เพื่อให้ทยอยบริหารจัดการได้ทัน</li>
                <li class="mb-2"><strong>ยาหมดอายุแล้ว:</strong> ตรวจจับและแจ้งเตือนทันทีเมื่อยาเลยวันหมดอายุ</li>
                <li class="mb-2"><strong>Stock ต่ำ (Low Stock):</strong> แจ้งเตือนเมื่อจำนวนยาในคลังลดลงต่ำกว่าจุดสั่งซื้อ (Reorder Point)</li>
              </ul>
              
              <p class="mb-3 font-weight-bold text-slate-800 mt-4">การตั้งค่ากฎ & ส่งเข้า Telegram</p>
              <p class="mb-3">
                คุณสามารถสร้าง <strong>"กฎการแจ้งเตือน"</strong> เพื่อให้ระบบเฝ้าระวังแบบเฉพาะเจาะจงได้ (เช่น เฝ้าระวังทั้งคลัง, คุมเฉพาะสถานที่, หรือระบุเฉพาะตัวยา) 
                นอกจากนี้ยังตั้งค่าให้ระบบ <strong>ส่งข้อความแจ้งเตือนตรงเข้าแอป Telegram</strong> ของหน่วยงานหรือส่วนตัวได้ทันทีโดยไม่ต้องเปิดหน้าเว็บทิ้งไว้
              </p>

              <p class="mb-3 font-weight-bold text-slate-800 mt-4">การจัดการปัญหาและปิดงาน (Resolve)</p>
              <p class="mb-3">
                ระบบมีกลไกป้องกันการแจ้งเตือนซ้ำซ้อนในวันเดียวกัน เมื่อคุณได้รับการแจ้งเตือน สามารถเข้าไปที่หน้าต่างแจ้งเตือนและกดปุ่ม <strong>"ปิด" (Resolve)</strong> เพื่อบันทึกว่าได้รับทราบและดำเนินการแก้ไขปัญหาเรียบร้อยแล้ว (เช่น สั่งยามาเติม หรือ นำยาหมดอายุไปทำลาย)
              </p>
            </v-expansion-panel-text>
          </v-expansion-panel>

        </v-expansion-panels>

        <div class="text-center py-8">
          <div class="text-slate-500 mb-4">หากพบปัญหาการใช้งาน หรือต้องการความช่วยเหลือเพิ่มเติม สามารถติดต่อได้ที่ฝ่าย IT</div>
          <v-btn color="slate-800" variant="outlined" prepend-icon="mdi-email" rounded="pill" class="font-weight-bold bg-white">
            ติดต่อฝ่ายสนับสนุน (Support)
          </v-btn>
        </div>
      </v-col>
    </v-row>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const activePanel = ref('dashboard')
</script>

<style scoped>
/* Color Palette */
.text-slate-900 { color: #0f172a; }
.text-slate-800 { color: #1e293b; }
.text-slate-600 { color: #475569; }
.text-slate-500 { color: #64748b; }
.border-slate { border: 1px solid rgba(148, 163, 184, 0.2) !important; }

.bg-green-50 { background-color: #f0fdf4; }
.bg-orange-50 { background-color: #fff7ed; }
.bg-blue-50 { background-color: #eff6ff; }

.help-page {
  display: flex;
  flex-direction: column;
}

/* Premium Hero Section */
.premium-hero {
  position: relative;
  overflow: hidden;
  background: linear-gradient(135deg, #ffffff 0%, #fdf2f8 100%);
  border: 1px solid rgba(225, 29, 72, 0.1);
  box-shadow: 0 20px 40px -15px rgba(225, 29, 72, 0.05);
}

.hero-bg-glow {
  position: absolute;
  top: -150px;
  right: -50px;
  width: 500px;
  height: 500px;
  background: radial-gradient(circle, rgba(225, 29, 72, 0.08) 0%, transparent 60%);
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

/* Panels */
.premium-panels :deep(.v-expansion-panel) {
  background: white;
  transition: all 0.2s ease;
  overflow: hidden;
}

.premium-panels :deep(.v-expansion-panel:hover) {
  box-shadow: 0 12px 24px -8px rgba(15, 23, 42, 0.08) !important;
  border-color: rgba(148, 163, 184, 0.4) !important;
}

.premium-panels :deep(.v-expansion-panel-title--active) {
  background-color: #f8fafc;
}

.panel-title {
  font-size: 1.15rem !important;
  color: #1e293b;
}

.movement-card {
  padding: 20px;
  border-radius: 16px;
  text-align: center;
  border: 1px solid rgba(148, 163, 184, 0.1);
  height: 100%;
}
</style>
