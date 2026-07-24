<div class="rounded-xl border border-blue-200 bg-blue-50 dark:bg-blue-950/30 dark:border-blue-800 p-4 text-sm text-blue-800 dark:text-blue-200 space-y-2">
    <p class="font-semibold flex items-center gap-2">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Format Kolom CSV yang Diperlukan:
    </p>
    <code class="block text-xs bg-white dark:bg-zinc-900 border border-blue-200 dark:border-blue-700 rounded-lg px-3 py-2 font-mono text-blue-900 dark:text-blue-100 overflow-x-auto whitespace-pre">set, question, option_a, option_b, option_c, option_d, correct_answer, type, tag, difficulty, is_active</code>
    <ul class="text-xs space-y-1 list-disc list-inside text-blue-700 dark:text-blue-300">
        <li><strong>set</strong>: A, B, atau C</li>
        <li><strong>correct_answer</strong>: a, b, c, atau d (huruf kecil)</li>
        <li><strong>type</strong>: grammar atau vocabulary</li>
        <li><strong>tag</strong>: basic, intermediate, atau advanced</li>
        <li><strong>difficulty</strong>: angka 1–5</li>
        <li><strong>is_active</strong>: 1 (aktif) atau 0 (nonaktif)</li>
    </ul>
    <a href="{{ asset('templates/questions_template.csv') }}"
       download
       class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-700 dark:text-blue-300 hover:text-blue-900 dark:hover:text-white underline underline-offset-2 transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        Unduh Contoh Template CSV
    </a>
</div>
