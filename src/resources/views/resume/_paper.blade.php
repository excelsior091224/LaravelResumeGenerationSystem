<div class="paper-header">
    <h2>職務経歴書</h2>
    <div class="paper-meta">{{ $resume['as_of_date'] ?? '' }}<br><b>氏名：{{ $resume['full_name'] ?? '' }}</b></div>
</div>

<div class="paper-section">
    <h3>■ 職務要約</h3>
    <p class="summary-text">@if (isset($resume['summary_html'])){!! $resume['summary_html'] !!}@else{{ $resume['summary'] ?? '' }}@endif</p>
</div>
<div class="paper-section">
    <h3>■ 得意業務</h3>
    <p>・ {{ $resume['specialty'] ?? '' }}</p>
</div>
<div class="paper-section">
    <h3>■ 技術系アカウント・ポートフォリオ</h3>
    @php
        $links = collect($resume['links'] ?? [])->filter(fn($link) => !empty($link['url']));
    @endphp
    @if ($links->isNotEmpty())
        <ul>
            @foreach ($links as $link)
                <li><span>{{ ($link['type'] ?? '') === 'その他' ? $link['type_custom'] ?? '' : $link['type'] ?? '' }}：</span>{{ $link['url'] }}
                </li>
            @endforeach
        </ul>
    @else
        <p class="empty-note">技術系アカウントやポートフォリオを入力してください</p>
    @endif
</div>

<div class="paper-section">
    <h3>■ PCスキル / テクニカルスキル</h3>
    @php
        $skillGroups = collect($resume['skills'] ?? [])
            ->filter(
                fn($skill) => ($skill['name'] ?? '') ||
                    ($skill['category'] ?? '') ||
                    ($skill['years'] ?? '') ||
                    ($skill['level'] ?? '') ||
                    ($skill['note'] ?? ''),
            )
            ->groupBy(fn($skill) => $skill['category'] ?: '未分類');
    @endphp
    <table class="paper-table">
        <colgroup>
            <col class="skill-category-column">
            <col class="skill-name-column">
            <col class="skill-years-column">
            <col class="skill-level-column">
            <col class="skill-note-column">
        </colgroup>
        <thead>
            <tr>
                <th>カテゴリ</th>
                <th>スキル</th>
                <th>経験年数</th>
                <th>経験区分</th>
                <th>備考</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($skillGroups as $category => $skills)
                @foreach ($skills as $index => $skill)
                    <tr>
                        <td>{{ $category }}</td>
                        <td>{{ $skill['name'] ?? '' }}</td>
                        <td>{{ $skill['years'] ?? '' }}</td>
                        <td>{{ $skill['level'] ?? '' }}</td>
                        <td>{{ $skill['note'] ?? '' }}</td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="5" class="empty-note">スキルを入力してください</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="paper-section">
    <h3>■ 職務経歴</h3>
    @php
        $companies = collect($resume['companies'] ?? [])
            ->sortByDesc('period_from')
            ->values();
    @endphp
    @forelse ($companies as $company)
        <div class="company-block">
            <p class="company-title">勤務先：{{ $company['name'] ?: (($company['employment_type'] ?? '') === 'フリーランス' ? 'フリーランス' : '所属企業名未入力') }}（{{ $company['period_from'] ?? '' }}〜{{ $company['period_to'] ?? '' }}）</p>
            @if (
                ($company['employment_type'] ?? '') ||
                    ($company['industry'] ?? '') ||
                    ($company['established'] ?? '') ||
                    ($company['capital'] ?? '') ||
                    ($company['employees'] ?? ''))
                <p class="project-detail">{{ collect([($company['employment_type'] ?? '') === 'その他' ? $company['employment_type_custom'] ?? '' : $company['employment_type'] ?? '', $company['industry'] ?? '', $company['established'] ?? '' ? '設立：' . $company['established'] : null, $company['capital'] ?? '' ? '資本金：' . $company['capital'] : null, $company['employees'] ?? '' ? '従業員数：' . $company['employees'] : null])->filter()->join(' / ') }}</p>
            @endif
            @if (!empty($company['business_overview']))
                <p class="project-detail"><b>【業務概要】</b><br>{{ $company['business_overview'] }}</p>
            @endif
            @foreach (collect($company['projects'] ?? [])->sortByDesc('period_from')->values() as $project)
                <div class="project-block">
                    <p class="project-title">■ {{ $project['name'] ?? '' }}（{{ $project['period_from'] ?? '' }}〜{{ $project['period_to'] ?? '' }}）</p>
                    <p class="project-detail">{{ $project['description'] ?? '' }}</p>
                    <p class="project-detail"><b>【担当工程】</b><br>{{ $project['processes'] ?? '' }}</p>
                    <p class="project-detail"><b>【使用技術・DB・OS】</b><br>{{ $project['technologies'] ?? '' }}</p>
                    <p class="project-detail"><b>【組織・役割】</b><br>{{ ($project['role'] ?? '') === 'その他' ? $project['role_custom'] ?? '' : $project['role'] ?? '' }} / {{ $project['team'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
    @empty
        <p class="empty-note">所属企業とプロジェクトを入力してください</p>
    @endforelse
</div>

<div class="paper-section">
    <h3>■ 資格</h3>
    @if (collect($resume['certifications'] ?? [])->where('name', '!=', '')->isNotEmpty())
        <ul>
            @foreach ($resume['certifications'] as $certification)
                @if (!empty($certification['name']))
                    @php
                        $certificationDate = $certification['date'] ?? '';
                        if (preg_match('/^\d{4}-\d{2}$/', $certificationDate)) {
                            $certificationDate = str_replace('-', '年', $certificationDate) . '月';
                        }
                    @endphp
                    <li>{{ $certificationDate }}　{{ $certification['name'] }}</li>
                @endif
            @endforeach
        </ul>
    @else
        <p class="empty-note">資格を入力してください</p>
    @endif
</div>
<div class="paper-section">
    <h3>■ 自己PR</h3>
    <p>@if (isset($resume['self_pr_html'])){!! $resume['self_pr_html'] !!}@else{{ $resume['self_pr'] ?? '' }}@endif</p>
</div>
@if (!empty($resume['considerations']))
    <div class="paper-section">
        <h3>■ 配慮事項</h3>
        <p>@if (isset($resume['considerations_html'])){!! $resume['considerations_html'] !!}@else{{ $resume['considerations'] }}@endif</p>
    </div>
@endif
<div class="paper-closing">
    <p class="paper-closing-end">以上</p>
    <p class="paper-closing-message">是非、面接の機会をいただければと思います。何卒よろしくお願いいたします。</p>
</div>
