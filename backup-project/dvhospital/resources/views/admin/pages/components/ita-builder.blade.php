<div id="itaBuilderContainer" style="display: none;">
    <div class="mb-3 d-flex justify-content-between align-items-center">
        <label class="form-label fw-medium mb-0">โครงสร้างหัวข้อ (ITA) <span class="text-danger">*</span></label>
        <button type="button" class="btn btn-sm btn-outline-success" onclick="addItaItem()">
            <i class="bi bi-plus-circle me-1"></i> เพิ่มหัวข้อหลัก
        </button>
    </div>
    <div id="itaItemsList" class="border rounded p-3 bg-light min-vh-50">
        <!-- Items will be generated here -->
    </div>
    <!-- Hidden input to store JSON data -->
    <input type="hidden" name="ita_json_content" id="itaJsonContent">
</div>

<template id="itaItemTemplate">
    <div class="ita-item card mb-2 border-1 shadow-sm" data-level="0">
        <div class="card-body p-2 d-flex align-items-center gap-2">
            <div class="ita-drag-handle text-muted cursor-move" style="cursor: grab;"><i class="bi bi-grip-vertical"></i></div>
            <div class="ita-number fw-bold text-primary" style="min-width: 45px;"></div>
            <input type="text" class="form-control form-control-sm ita-text-input" placeholder="กรอกข้อความหัวข้อ..." required>
            
            <button type="button" class="btn btn-sm btn-light text-success add-subitem-btn" title="เพิ่มหัวข้อย่อย">
                <i class="bi bi-node-plus"></i>
            </button>
            <button type="button" class="btn btn-sm btn-light text-danger delete-item-btn" title="ลบหัวข้อ">
                <i class="bi bi-trash"></i>
            </button>
        </div>
        <div class="ita-children ps-4 pb-2 pe-2 d-none">
            <!-- Sub items -->
        </div>
    </div>
</template>

@push('styles')
<style>
    .ita-children:not(:empty) {
        display: block !important;
        border-left: 2px dashed #dee2e6;
        margin-left: 1.5rem;
    }
    .ita-item { background-color: #fff; }
    .ita-drag-handle:active { cursor: grabbing !important; }
</style>
@endpush

@push('scripts')
<script>
    let itaData = [];

    // Parse existing JSON if any
    const existingContent = document.querySelector('textarea[name="content"]').value;
    if (existingContent.trim() !== '') {
        try {
            const parsed = JSON.parse(existingContent);
            if (Array.isArray(parsed)) {
                itaData = parsed;
            }
        } catch (e) {
            // Not JSON, it's flat text. Convert to a simple flat list to prevent data loss.
            const lines = existingContent.split('\n');
            itaData = lines.map(line => {
                const text = line.trim();
                if (text) {
                    // Try to strip leading numbers just for display if it matches standard format
                    const match = text.match(/^(?:\d+(?:\.\d+)*)\.?\s*(.+)$/);
                    return { text: match ? match[1] : text, children: [] };
                }
                return null;
            }).filter(item => item !== null);
        }
    }

    const sectionSelect = document.querySelector('select[name="section"]');
    const normalContentArea = document.querySelector('textarea[name="content"]').closest('.mb-3');
    const builderContainer = document.getElementById('itaBuilderContainer');
    const jsonInput = document.getElementById('itaJsonContent');
    const itemsList = document.getElementById('itaItemsList');
    const template = document.getElementById('itaItemTemplate');

    function isItaSection(val) {
        return val === 'ita' || val.startsWith('ita_');
    }

    function toggleBuilder() {
        if (isItaSection(sectionSelect.value)) {
            normalContentArea.style.display = 'none';
            document.querySelector('textarea[name="content"]').removeAttribute('required');
            builderContainer.style.display = 'block';
            renderItaList();
        } else {
            normalContentArea.style.display = 'block';
            document.querySelector('textarea[name="content"]').setAttribute('required', 'required');
            builderContainer.style.display = 'none';
        }
    }

    sectionSelect.addEventListener('change', toggleBuilder);
    // Init on load
    toggleBuilder();

    function renderItaList() {
        itemsList.innerHTML = '';
        itaData.forEach((item, index) => {
            itemsList.appendChild(createItemElement(item, [index + 1]));
        });
        updateJsonInput();
    }

    function getItemByPath(pathArr) {
        let currentArr = itaData;
        let item = null;
        for (let i = 0; i < pathArr.length; i++) {
            item = currentArr[pathArr[i] - 1];
            if (i < pathArr.length - 1) currentArr = item.children;
        }
        return { item, arr: pathArr.length === 1 ? itaData : getParentArr(pathArr) };
    }

    function getParentArr(pathArr) {
        let currentArr = itaData;
        for (let i = 0; i < pathArr.length - 2; i++) {
            currentArr = currentArr[pathArr[i] - 1].children;
        }
        return currentArr[pathArr[pathArr.length - 2] - 1].children;
    }

    function moveItemByPath(pathArr, direction) {
        let arr = pathArr.length === 1 ? itaData : getParentArr(pathArr);
        const idx = pathArr[pathArr.length - 1] - 1;
        const targetIdx = direction === 'up' ? idx - 1 : idx + 1;
        if (targetIdx < 0 || targetIdx >= arr.length) return;
        const temp = arr[idx];
        arr[idx] = arr[targetIdx];
        arr[targetIdx] = temp;
        renderItaList();
    }

    function createItemElement(item, pathArr) {
        const clone = template.content.cloneNode(true);
        const itemDiv = clone.querySelector('.ita-item');
        const numberDiv = clone.querySelector('.ita-number');
        const input = clone.querySelector('.ita-text-input');
        const childrenDiv = clone.querySelector('.ita-children');
        const addBtn = clone.querySelector('.add-subitem-btn');
        const delBtn = clone.querySelector('.delete-item-btn');

        const numberStr = pathArr.join('.');
        numberDiv.textContent = numberStr;
        input.value = item.text || '';

        // --- เพิ่มปุ่มเลื่อนลำดับ ---
        const btnGroup = addBtn.parentElement;

        const moveUpBtn = document.createElement('button');
        moveUpBtn.type = 'button';
        moveUpBtn.className = 'btn btn-sm btn-light text-secondary';
        moveUpBtn.title = 'เลื่อนขึ้น';
        moveUpBtn.innerHTML = '<i class="bi bi-arrow-up"></i>';
        moveUpBtn.addEventListener('click', () => moveItemByPath(pathArr, 'up'));

        const moveDownBtn = document.createElement('button');
        moveDownBtn.type = 'button';
        moveDownBtn.className = 'btn btn-sm btn-light text-secondary';
        moveDownBtn.title = 'เลื่อนลง';
        moveDownBtn.innerHTML = '<i class="bi bi-arrow-down"></i>';
        moveDownBtn.addEventListener('click', () => moveItemByPath(pathArr, 'down'));

        btnGroup.insertBefore(moveUpBtn, addBtn);
        btnGroup.insertBefore(moveDownBtn, addBtn);
        // ----------------------------

        // Add event listeners
        input.addEventListener('input', (e) => {
            item.text = e.target.value;
            updateJsonInput();
        });

        addBtn.addEventListener('click', () => {
            if (!item.children) item.children = [];
            item.children.push({ text: '', children: [] });
            renderItaList();
        });

        delBtn.addEventListener('click', () => {
            if (confirm('ยืนยันการลบหัวข้อนี้และหัวข้อย่อยทั้งหมด?')) {
                deleteItemByPath(pathArr);
                renderItaList();
            }
        });

        if (item.children && item.children.length > 0) {
            item.children.forEach((child, childIndex) => {
                childrenDiv.appendChild(createItemElement(child, [...pathArr, childIndex + 1]));
            });
        }

        return itemDiv;
    }

    function addItaItem() {
        itaData.push({ text: '', children: [] });
        renderItaList();
    }

    function deleteItemByPath(pathArr) {
        let currentArr = itaData;
        for (let i = 0; i < pathArr.length - 1; i++) {
            currentArr = currentArr[pathArr[i] - 1].children;
        }
        currentArr.splice(pathArr[pathArr.length - 1] - 1, 1);
    }

    function updateJsonInput() {
        jsonInput.value = JSON.stringify(itaData);
    }

    // Intercept form submit to copy jsonInput back to textarea if section is ita
    const pageForm = document.getElementById('itaBuilderContainer').closest('form');
    if (pageForm) {
        pageForm.addEventListener('submit', function(e) {
            if (isItaSection(sectionSelect.value)) {
                document.querySelector('textarea[name="content"]').value = jsonInput.value;
            }
        });
    }

</script>
@endpush
