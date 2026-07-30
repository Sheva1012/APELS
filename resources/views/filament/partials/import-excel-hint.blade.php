<div style="border: 1px solid #bfdbfe; background-color: #eff6ff; padding: 16px; border-radius: 12px; margin-bottom: 16px; color: #1e40af;" class="dark:bg-blue-950/30 dark:border-blue-800 dark:text-blue-200">
    <p style="font-weight: 600; display: flex; align-items: center; gap: 8px;">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 20px; height: 20px; flex-shrink: 0;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Format Kolom Excel yang Diperlukan:
    </p>
    <code style="display: block; font-size: 0.75rem; background-color: #ffffff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 8px 12px; font-family: monospace; color: #1e3a8a; overflow-x: auto; white-space: pre; margin-top: 8px; margin-bottom: 8px;" class="dark:bg-zinc-900 dark:border-blue-700 dark:text-blue-100">set, question, option_a, option_b, option_c, option_d, correct_answer, type, tag, difficulty, is_active</code>
    <ul style="font-size: 0.75rem; line-height: 1.5; list-style-type: disc; list-style-position: inside; color: #1d4ed8; margin-bottom: 12px;" class="dark:text-blue-300">
        <li><strong>set</strong>: A, B, atau C</li>
        <li><strong>correct_answer</strong>: a, b, c, atau d (huruf kecil)</li>
        <li><strong>type</strong>: grammar atau vocabulary</li>
        <li><strong>tag</strong>: basic, intermediate, atau advanced</li>
        <li><strong>difficulty</strong>: angka 1–5</li>
        <li><strong>is_active</strong>: 1 (aktif) atau 0 (nonaktif)</li>
    </ul>
    <a href="{{ asset('templates/question_import_template.xlsx') }}"
       download
       style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.75rem; font-weight: bold; color: #3b82f6; text-decoration: underline;">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 14px; height: 14px;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        Unduh Contoh Template Excel (.xlsx)
    </a>
</div>
