<div class="p-4 bg-white rounded-lg shadow-lg" style="height: auto;"
    x-data="{
        collapsed: {},
        processing: false,
        init() {
            this.$nextTick(() => this.processTable());
            document.addEventListener('livewire:morphed', () => {
                this.$nextTick(() => this.processTable());
            });
        },
        processTable() {
            if (this.processing) return;
            this.processing = true;
            const cells = this.$el.querySelectorAll('[data-company-first=\'1\']');
            cells.forEach(cell => {
                if (cell.querySelector('.group-toggle')) return;
                const company = cell.dataset.company;
                const textEl = cell.querySelector('.fi-ta-text-item-inner') || cell.querySelector('.fi-ta-text-item') || cell.querySelector('a') || cell;
                const toggle = document.createElement('span');
                toggle.className = 'group-toggle';
                toggle.style.cssText = 'cursor: pointer; margin-right: 6px; display: inline-block; font-size: 0.7rem; user-select: none; color: #6b7280;';
                toggle.textContent = this.collapsed[company] ? '▶' : '▼';
                toggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    this.collapsed[company] = !this.collapsed[company];
                    this.applyVisibility();
                });
                textEl.parentNode.insertBefore(toggle, textEl);
            });
            this.applyVisibility();
            this.processing = false;
        },
        applyVisibility() {
            const rows = this.$el.querySelectorAll('table tbody tr');
            let currentCompany = null;
            rows.forEach(row => {
                const cell = row.querySelector('[data-company]');
                if (!cell) return;
                const company = cell.dataset.company;
                const isFirst = cell.dataset.companyFirst === '1';
                if (isFirst) {
                    currentCompany = company;
                    row.style.display = '';
                    const toggle = cell.querySelector('.group-toggle');
                    if (toggle) toggle.textContent = this.collapsed[company] ? '▶' : '▼';
                } else {
                    row.style.display = this.collapsed[currentCompany] ? 'none' : '';
                }
            });
        }
    }">
    <div class="flex items-center justify-end mb-4">
        <span class="text-sm font-medium text-gray-600">
            Total Records: <span class="font-bold text-gray-900">{{ number_format($this->getTableRecords()->total()) }}</span>
        </span>
    </div>
    {{ $this->table }}
    @if ($this->getTableRecords()->total() > 0 && $this->getTableRecords()->lastPage() > 1)
        <div class="mt-4 text-sm text-center text-gray-600">
            Page {{ $this->getTableRecords()->currentPage() }} of {{ $this->getTableRecords()->lastPage() }}
        </div>
    @endif
</div>
