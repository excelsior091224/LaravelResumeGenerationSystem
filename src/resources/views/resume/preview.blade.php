<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>職務経歴書プレビュー</title>@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    {{-- サーバーでバリデーション済みの入力内容を帳票形式で表示する。 --}}
    <main class="paper-wrap">
        <article class="paper">
            <div class="paper-header">
                <h2>職務経歴書</h2>
                <div class="paper-meta">{{ $resume['as_of_date'] ?? '' }}<br><b>氏名：{{ $resume['full_name'] ?? '' }}</b>
                </div>
            </div>
            <div class="paper-section">
                <h3>■ 職務要約</h3>
                <p>{{ $resume['summary'] ?? '' }}</p>
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
                            <li><span>{{ ($link['type'] ?? '') === 'その他' ? $link['type_custom'] ?? '' : $link['type'] ?? '' }}：</span><a
                                    href="{{ $link['url'] }}" target="_blank" rel="noreferrer">{{ $link['url'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="empty-note">技術系アカウントやポートフォリオを入力してください</p>
                @endif
            </div>
            <div class="paper-section">
                <h3>■ PCスキル / テクニカルスキル</h3>
                <table class="paper-table">
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
                        {{-- 入力順ではなく、最初に登場したカテゴリ順でスキルをまとめる。 --}}
                        @php
                            $skillGroups = collect($resume['skills'] ?? [])
                                ->filter(
                                    fn($skill) => $skill['name'] ||
                                        $skill['category'] ||
                                        $skill['years'] ||
                                        $skill['level'] ||
                                        $skill['note'],
                                )
                                ->groupBy(fn($skill) => $skill['category'] ?: '未分類');
                        @endphp
                        @forelse ($skillGroups as $category => $skills)
                            @foreach ($skills as $index => $skill)
                                <tr>
                                    @if ($index === 0)
                                        <td rowspan="{{ $skills->count() }}">{{ $category }}</td>
                                    @endif
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
                @foreach ($companies as $company)
                    <div class="company-block">
                        <p class="company-title">
                            勤務先：{{ $company['name'] ?: (($company['employment_type'] ?? '') === 'フリーランス' ? 'フリーランス' : '所属企業名未入力') }}（{{ $company['period_from'] ?? '' }}〜{{ $company['period_to'] ?? '' }}）
                        </p>
                        @if (
                            ($company['employment_type'] ?? '') ||
                                $company['industry'] ||
                                $company['established'] ||
                                $company['capital'] ||
                                $company['employees']
                        )
                            <p class="project-detail">
                                {{ collect([($company['employment_type'] ?? '') === 'その他' ? $company['employment_type_custom'] ?? '' : $company['employment_type'] ?? '', $company['industry'], $company['established'] ? '設立：' . $company['established'] : null, $company['capital'] ? '資本金：' . $company['capital'] : null, $company['employees'] ? '従業員数：' . $company['employees'] : null])->filter()->join(' / ') }}
                            </p>
                        @endif
                        @foreach (collect($company['projects'] ?? [])->sortByDesc('period_from')->values() as $project)
                            <div class="project-block">
                                <p class="project-title">■
                                    {{ $project['name'] }}（{{ $project['period_from'] ?? '' }}〜{{ $project['period_to'] ?? '' }}）
                                </p>
                                <p class="project-detail">{{ $project['description'] }}</p>
                                <p class="project-detail"><b>【担当工程】</b><br>{{ $project['processes'] }}</p>
                                <p class="project-detail"><b>【使用技術・DB・OS】</b><br>{{ $project['technologies'] }}</p>
                                <p class="project-detail">
                                    <b>【組織・役割】</b><br>{{ ($project['role'] ?? '') === 'その他' ? $project['role_custom'] ?? '' : $project['role'] ?? '' }}
                                    /
                                    {{ $project['team'] }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
            <div class="paper-columns">
                <div class="paper-section">
                    <h3>■ 資格</h3>
                    <ul>
                        @foreach ($resume['certifications'] ?? [] as $certification)
                            <li>{{ $certification['date'] }}　{{ $certification['name'] }}</li>
                        @endforeach
                    </ul>
                </div>
                <div class="paper-section">
                    <h3>■ 自己PR</h3>
                    <p>{{ $resume['self_pr'] ?? '' }}</p>
                </div>
            </div>
        </article>
    </main>
</body>

</html>
