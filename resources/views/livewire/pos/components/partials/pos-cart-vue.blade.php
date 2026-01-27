<div class="w-[520px] bg-gradient-to-b from-white to-gray-50 border-l-2 border-gray-200 shadow-2xl flex flex-col overflow-hidden"
    style="height: calc(100vh - 64px);">
    
    <!-- Vue.js Cart Component -->
    <div id="vue-pos-cart" class="flex-1 overflow-hidden flex flex-col"
        data-clients="{{ json_encode($clients) }}"
        data-currency="{{ current_currency() }}">
    </div>
</div>

@push('scripts')
<script>
    // Toast notifications handler
    window.addEventListener('show-toast', (event) => {
        const { type, message } = event.detail;
        
        // Use Alpine toast if available
        if (window.Alpine && Alpine.store('toast')) {
            if (type === 'success') Alpine.store('toast').success(message);
            else if (type === 'error') Alpine.store('toast').error(message);
            else Alpine.store('toast').info(message);
        } else {
            console.log(`[Toast ${type}]`, message);
        }
    });
</script>
@endpush
