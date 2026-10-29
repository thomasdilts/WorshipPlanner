<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('serviceOrderDrag', () => ({
            observer: null,
            frame: null,
            configuredSortable: null,
            reconcile() {
                cancelAnimationFrame(this.frame);
                this.frame = requestAnimationFrame(() => {
                    const table = this.$el.closest('.fi-ta');
                    if (!table?.isConnected) return;

                    table.querySelectorAll('.wp-order-drag-handle[data-order-record-key]').forEach(handle => {
                        const row = handle.closest('tr');
                        if (row && row.getAttribute('x-sortable-item') !== handle.dataset.orderRecordKey) {
                            row.setAttribute('x-sortable-item', handle.dataset.orderRecordKey);
                        }
                    });

                    const sortable = table.querySelector('tbody.fi-ta-records')?.sortable;
                    if (!sortable || sortable === this.configuredSortable) return;
                    this.configuredSortable = sortable;
                    sortable.option('animation', matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 220);
                    sortable.option('easing', 'cubic-bezier(0.2, 0, 0, 1)');
                    sortable.option('ghostClass', 'wp-order-drag-ghost');
                    sortable.option('chosenClass', 'wp-order-drag-chosen');
                    sortable.option('delay', 0);
                });
            },
            init() {
                this.observer = new MutationObserver(() => this.reconcile());
                this.observer.observe(this.$el.closest('.fi-ta'), {
                    childList: true,
                    subtree: true,
                    attributes: true,
                    attributeFilter: ['x-sortable-item', 'data-order-record-key'],
                });
                this.$nextTick(() => this.reconcile());
            },
            destroy() {
                this.observer?.disconnect();
                cancelAnimationFrame(this.frame);
            },
        }));
    });
</script>
