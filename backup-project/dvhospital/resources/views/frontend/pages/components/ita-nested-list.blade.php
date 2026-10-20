@php
    // เรียงลำดับข้อย่อยตามตำแหน่งใน array ก่อน render
    // รองรับกรณีที่ข้อมูลใน JSON อาจไม่ได้เรียงไว้ถูกต้อง
    $sortedItems = collect($items)->values()->all();
@endphp
@foreach($sortedItems as $index => $item)
    @php
        $currentPath = array_merge($path, [$index + 1]);
        $itemKey = implode('.', $currentPath);
        $hasChildren = !empty($item['children']);
        
        // Construct the full text (prefix + text)
        $fullText = $itemKey . ' ' . ($item['text'] ?? '');
    @endphp

    @php
        $iconClass = $hasChildren ? 'bi-folder2-open' : 'bi-file-earmark-text';
    @endphp

    <div class="list-group-item border-0 px-0 py-1 bg-transparent">
        <div class="d-flex align-items-md-center justify-content-between flex-column flex-md-row p-2 p-md-3 rounded-4 ita-row-hover transition-all" 
             style="cursor: {{ $hasChildren ? 'pointer' : 'default' }};"
             @if($hasChildren) data-bs-toggle="collapse" data-bs-target="#collapse-{{ str_replace('.', '-', $itemKey) }}" aria-expanded="true" @endif>
            
            <div class="d-flex align-items-start align-items-md-center flex-grow-1">
                <div class="me-3 text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px; background: rgba(13, 110, 253, 0.1); border-radius: 50%;">
                    <i class="bi {{ $iconClass }} fs-5"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-bold text-dark" style="font-size: 1.05rem;">{{ $fullText }}</div>
                    
                    {{-- Attachments --}}
                    @if(!$hasChildren && $page->attachments && $itemKey)
                        <div class="mt-2 d-flex flex-column align-items-start gap-2" onclick="event.stopPropagation();">
                            @foreach($page->attachments as $file)
                                @if(preg_match('/^' . preg_quote($itemKey, '/') . '(\s|$)/u', $file['name']))
                                    @if(isset($file['type']) && $file['type'] === 'link')
                                        <a href="{{ $file['path'] }}" class="btn btn-light btn-sm border shadow-sm py-1 px-3 rounded-pill text-info hover-info d-inline-flex align-items-start text-start" target="_blank">
                                            <i class="bi bi-link-45deg me-2 fs-5 mt-1"></i>
                                            <span>{{ $file['name'] }}</span>
                                        </a>
                                    @else
                                        <a href="{{ Storage::url($file['path']) }}" class="btn btn-light btn-sm border shadow-sm py-1 px-3 rounded-pill text-primary hover-primary d-inline-flex align-items-start text-start" target="_blank">
                                            <i class="bi bi-file-earmark-pdf me-2 fs-5 mt-1"></i>
                                            <span>{{ $file['name'] }}</span>
                                        </a>
                                    @endif
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="ms-md-3 mt-3 mt-md-0 d-flex gap-2 align-items-center flex-shrink-0" onclick="event.stopPropagation();">
                @if(!$hasChildren && auth()->check() && (auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('staff_ita')))
                    <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 py-1 btn-upload-attachment" 
                        data-page-id="{{ $page->id }}" 
                        data-item-key="{{ $itemKey }}" 
                        data-item-text="{{ $fullText }}">
                        <i class="bi bi-cloud-upload"></i> อัปโหลด
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-3 py-1 btn-edit-attachments" 
                        data-page-id="{{ $page->id }}" 
                        data-item-key="{{ $itemKey }}"
                        data-page-attachments="{{ json_encode($page->attachments ?? []) }}">
                        <i class="bi bi-pencil"></i> แก้ไข
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1 btn-delete-attachments" 
                        data-page-id="{{ $page->id }}" 
                        data-item-key="{{ $itemKey }}"
                        data-page-attachments="{{ json_encode($page->attachments ?? []) }}">
                        <i class="bi bi-trash"></i> ลบ
                    </button>
                @endif
                
                @if($hasChildren)
                    <div class="text-muted ms-2">
                        <i class="bi bi-chevron-down fs-5 collapse-icon"></i>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    @if($hasChildren)
        <div class="collapse show" id="collapse-{{ str_replace('.', '-', $itemKey) }}">
            <div class="nested-children ps-3 ps-md-4 border-start border-2 border-light ms-2 ms-md-3">
                @include('frontend.pages.components.ita-nested-list', ['items' => $item['children'], 'level' => $level + 1, 'path' => $currentPath, 'page' => $page])
            </div>
        </div>
    @endif
@endforeach
