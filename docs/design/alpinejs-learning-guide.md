# Alpine.js 学習ガイド

## 1. この文書の目的

この文書は、Alpine.jsを初めて使う人が、現在の職務経歴書生成アプリケーションのコードを読みながら基本を学べるようにまとめたものです。

一般的なAlpine.jsの説明だけでなく、次の実装に対応させて説明します。

- 職務経歴書フォームの状態管理
- スキル、リンク、資格の追加・削除
- 所属企業とプロジェクトの階層入力
- 入力内容のライブプレビュー
- Laravel BladeからAlpine.jsへ初期データを渡す処理
- フォームデータをLaravelの配列として送信する方法

対象コード:

```text
src/resources/js/resume-form.js
src/resources/views/resume/create.blade.php
src/resources/js/app.js
src/package.json
```

## 2. Alpine.jsとは

Alpine.jsは、HTMLに専用の属性を追加して、少量のJavaScriptで画面を動的にするための軽量なJavaScriptフレームワークです。

Vue.jsやReactのように大規模なコンポーネント構成を前提とせず、Bladeテンプレートへ少しずつ動きを追加したい場合に向いています。

今回のアプリでは、ページ全体をSPA化するのではなく、次のような画面内の操作に使っています。

- 入力欄を増やす
- 入力欄を削除する
- 選択したカテゴリに応じて候補を変える
- 「その他」を選んだときだけ入力欄を表示する
- 入力内容を右側のプレビューへ即時反映する

Alpine.jsはサーバーの代わりになるものではありません。入力値の一時的な管理や画面操作はAlpine.jsが担当し、最終的なバリデーションやファイル生成はLaravelが担当します。

## 3. 導入方法

このプロジェクトではnpmパッケージとして導入しています。

```json
"dependencies": {
    "alpinejs": "^3.16.1"
}
```

JavaScriptのエントリーポイントから読み込みます。

```javascript
// resources/js/app.js
import "./resume-form.js";
```

フォーム側のJavaScriptでAlpine.jsを起動します。

```javascript
// resources/js/resume-form.js
import Alpine from "alpinejs";

window.Alpine = Alpine;
Alpine.start();
```

`Alpine.start()`を呼ぶことで、HTML内の`x-data`などのAlpine.js属性が解釈され、画面の動作が始まります。

## 4. まず覚える記法

| 記法      | 役割                                           |
| --------- | ---------------------------------------------- |
| `x-data`  | Alpine.jsの状態とメソッドを定義する            |
| `x-model` | 入力値とJavaScriptの値を双方向に同期する       |
| `x-text`  | 値をテキストとして表示する                     |
| `x-html`  | HTML文字列を画面へ挿入する                     |
| `x-for`   | 配列の要素を繰り返し表示する                   |
| `x-show`  | 条件に応じて表示・非表示を切り替える           |
| `@click`  | クリック時に処理を実行する                     |
| `@change` | selectなどの値が変更されたときに処理を実行する |
| `:name`   | JavaScriptの値を使ってHTML属性を動的に作る     |
| `:list`   | JavaScriptの値を使ってdatalistのIDを指定する   |
| `:key`    | 繰り返し要素を識別するためのキーを指定する     |

`@click`は`x-on:click`の短縮記法です。

```blade
<button @click="addProject">案件を追加</button>
```

これは次と同じ意味です。

```blade
<button x-on:click="addProject">案件を追加</button>
```

## 5. `x-data`: Alpine.jsの開始地点

現在の画面では、ルート要素に`x-data`を付けています。

```blade
<div
    class="resume-shell"
    x-data="resumeForm({{ Js::from($skillCategories) }}, {{ Js::from($teamRoles) }})"
>
```

`x-data`は、Alpine.jsの状態を作る境界です。この要素の内側にあるHTMLから、`resume`や`addProject()`などを利用できます。

`resumeForm()`はJavaScriptで定義されています。

```javascript
window.resumeForm = (skillData, roleData) => ({
  categories: Object.entries(skillData).map(([key, category]) => ({
    key,
    ...category,
  })),
  roleGroups: roleData,
  resume: {
    full_name: "",
    as_of_date: new Date().toISOString().slice(0, 10),
    links: [blankLink()],
    summary: "",
    specialty: "",
    self_pr: "",
    skills: [blankSkill()],
    companies: [blankCompany()],
    certifications: [blankCertification()],
  },
});
```

この関数が返すオブジェクトが、画面で利用できる状態と操作です。

```text
x-data
  ↓
resumeForm()を実行
  ↓
resume、categories、roleGroups、メソッドを作成
  ↓
配下のBladeから参照
```

## 6. `x-model`: 入力欄と状態を同期する

氏名の入力欄は次のようになっています。

```blade
<input
    name="full_name"
    x-model="resume.full_name"
>
```

`x-model`を使うと、ユーザーが入力した値が`resume.full_name`へ反映されます。

```text
ユーザーが入力
    ↓
resume.full_nameが更新
    ↓
Alpine.jsが変更を検知
    ↓
プレビューも再描画
```

逆方向も可能です。

```javascript
this.resume.full_name = "山田太郎";
```

この値を変更すると、対応する入力欄にも値が表示されます。

Reactでいうstate、Vueでいうdataやrefに近い役割です。

## 7. 追加ボタンの仕組み

プロジェクト追加ボタンは次の記述です。

```blade
<button
    type="button"
    class="btn btn-secondary"
    @click="addProject(companyIndex)"
>
    案件を追加
</button>
```

クリックすると、JavaScriptのメソッドが実行されます。

```javascript
addProject(companyIndex) {
    this.resume.companies[companyIndex].projects.push(blankProject());
},
```

`blankProject()`は新しい空のプロジェクトオブジェクトを返します。

```javascript
const blankProject = () => ({
  id: crypto.randomUUID(),
  period_from: "",
  period_to: "",
  name: "",
  description: "",
  role: "",
  role_custom: "",
  team: "",
  processes: "",
  technologies: "",
});
```

処理の流れ:

```text
案件を追加ボタンをクリック
    ↓
@click="addProject(companyIndex)"
    ↓
blankProject()で空の案件を作成
    ↓
対象企業のprojects配列へpush
    ↓
Alpine.jsが変更を検知
    ↓
x-forが新しい入力欄を表示
```

`type="button"`を指定しているため、追加ボタンを押してもフォーム送信は起きません。

## 8. `x-for`: 繰り返し入力欄を作る

企業とプロジェクトは入れ子の`x-for`で表示しています。

```blade
<template x-for="(company, companyIndex) in resume.companies" :key="company.id">
    <div class="repeatable-item">
        <template x-for="(project, projectIndex) in company.projects" :key="project.id">
            <div class="nested-item">
                <!-- プロジェクト入力欄 -->
            </div>
        </template>
    </div>
</template>
```

配列が次の状態なら、

```javascript
resume.companies = [
  {
    name: "企業A",
    projects: [{ name: "案件A-1" }, { name: "案件A-2" }],
  },
  {
    name: "企業B",
    projects: [{ name: "案件B-1" }],
  },
];
```

画面には次の構造が生成されます。

```text
企業A
  案件A-1
  案件A-2
企業B
  案件B-1
```

### `:key`の意味

```blade
:key="company.id"
:key="project.id"
```

`:key`は、繰り返し要素をAlpine.jsが識別するための値です。

各要素の生成時に`crypto.randomUUID()`で一意なIDを作っています。

```javascript
id: crypto.randomUUID();
```

配列の途中を削除した場合でも、どの入力欄がどのデータに対応するかを安定して管理できます。

## 9. 追加・削除メソッド

企業の追加:

```javascript
addCompany() {
    this.resume.companies.push(blankCompany());
},
```

企業の削除:

```javascript
removeCompany(index) {
    this.resume.companies.splice(index, 1);
},
```

プロジェクトの削除:

```javascript
removeProject(companyIndex, projectIndex) {
    this.resume.companies[companyIndex].projects.splice(projectIndex, 1);
},
```

`splice(index, 1)`は、配列の`index`番目から1件削除するJavaScriptのメソッドです。

```javascript
const items = ["A", "B", "C"];
items.splice(1, 1);
// ["A", "C"]
```

削除ボタンは、プロジェクトが1件だけのときは表示しないようにしています。

```blade
<button
    @click="removeProject(companyIndex, projectIndex)"
    x-show="company.projects.length > 1"
>
    削除
</button>
```

## 10. `x-show`: 条件付き表示

「その他」を選択したときだけ自由入力欄を表示しています。

```blade
<input
    x-show="project.role === 'その他'"
    x-model="project.role_custom"
    placeholder="具体的な役割を入力"
>
```

`project.role`が`その他`なら表示され、それ以外なら非表示になります。

リンク種別でも同じ仕組みを使っています。

```blade
<input
    x-show="link.type === 'その他'"
    x-model="link.type_custom"
    placeholder="サイト名を入力"
>
```

`x-show`は要素をDOMから完全に削除するのではなく、表示状態を切り替えます。入力値を残したまま一時的に隠したい場合に向いています。

## 11. `@change`: 選択内容に応じた処理

スキルカテゴリを変更したら、前のカテゴリのスキル名を消しています。

```blade
<select
    x-model="skill.category"
    @change="skill.name = ''"
>
```

例えば、カテゴリを「言語」から「フレームワーク」へ変更したとき、以前入力していた`PHP`を残すと不整合になる可能性があります。そのためカテゴリ変更時にスキル名をクリアします。

## 12. 動的なHTML属性

プロジェクトの期間入力では、配列の添字を使って`name`属性を作っています。

```blade
<input
    :name="`companies[${companyIndex}][projects][${projectIndex}][period_from]`"
    x-model="project.period_from"
>
```

1件目は次のようなHTMLになります。

```html
<input name="companies[0][projects][0][period_from]" />
```

2社目の1件目なら次のようになります。

```html
<input name="companies[1][projects][0][period_from]" />
```

Laravelはこの名前を配列として受け取れます。

```php
$request->input('companies');
```

サーバー側では概念的に次のデータになります。

```php
[
    [
        'name' => '企業A',
        'projects' => [
            [
                'period_from' => '2025-01',
                'name' => '案件A',
            ],
        ],
    ],
]
```

`:`が付いた属性は、固定文字列ではなくJavaScriptの式から値を作るという意味です。

## 13. スキル候補の絞り込み

スキル入力欄には、行ごとに異なる`datalist`を指定しています。

```blade
<input
    :list="`skill-options-${index}`"
    x-model="skill.name"
>
```

候補リスト側では、選択中のカテゴリと一致するものだけを生成します。

```blade
<template x-for="(skill, index) in resume.skills" :key="skill.id">
    <datalist :id="`skill-options-${index}`">
        <template x-for="category in categories" :key="category.key">
            <template x-if="category.label === skill.category">
                <template x-for="name in category.skills" :key="name">
                    <option :value="name"></option>
                </template>
            </template>
        </template>
    </datalist>
</template>
```

これにより、カテゴリが「言語」ならPHPやJavaScript、「フレームワーク」ならLaravelやReactのように候補を絞れます。

候補データはLaravel側の静的JSONからBladeへ渡しています。

```blade
x-data="resumeForm({{ Js::from($skillCategories) }}, {{ Js::from($teamRoles) }})"
```

`Js::from()`は、PHPの配列をJavaScriptへ安全に渡すためのLaravelのヘルパーです。

## 14. ライブプレビュー

プレビュー領域は次の記述です。

```blade
<div class="paper" x-html="renderPreview()"></div>
```

`renderPreview()`がHTML文字列を返し、`x-html`がその文字列を画面へ挿入します。

```javascript
renderPreview() {
    const r = this.resume;

    return `
        <div class="paper-header">
            <h2>職務経歴書</h2>
            <b>氏名：${this.escape(r.full_name)}</b>
        </div>
    `;
},
```

`x-model`で状態が変化すると、Alpine.jsは依存している表示を更新します。そのため、氏名やスキルを入力するとライブプレビューも更新されます。

### スキルのカテゴリまとめ

スキルは入力順ではなくカテゴリごとにまとめています。

```javascript
const skillGroups = this.resume.skills
  .filter((skill) => skill.name || skill.category)
  .reduce((groups, skill) => {
    const category = skill.category || "未分類";
    groups[category] ??= [];
    groups[category].push(skill);
    return groups;
  }, {});
```

例えば入力順が「言語、フレームワーク、言語」でも、表示時は「言語、言語、フレームワーク」のグループになります。

### 職歴の新しい順表示

企業とプロジェクトは、開始年月を比較して新しい順に並べています。

```javascript
const companies = [...this.resume.companies].sort((a, b) =>
  (b.period_from || "").localeCompare(a.period_from || ""),
);
```

配列を直接変更せず、スプレッド構文でコピーしてから`sort()`している点が重要です。

```javascript
const sortedCompanies = [...this.resume.companies].sort(...);
```

## 15. `x-html`と安全性

`x-html`はHTML文字列を直接挿入するため、ユーザー入力をそのまま埋め込むのは危険です。

このアプリでは、次の`escape()`でHTML特殊文字を変換しています。

```javascript
escape(value) {
    return String(value || "").replace(
        /[&<>"']/g,
        (character) =>
            ({
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#039;",
            })[character],
    );
},
```

例えば次の入力を、

```html
<script>
  alert("危険");
</script>
```

HTMLとして実行されない文字列に変換します。

URLについては、さらに`http`と`https`だけを許可しています。

```javascript
safeLinkUrl(url) {
    try {
        const parsedUrl = new URL(url);
        return ["http:", "https:"].includes(parsedUrl.protocol)
            ? parsedUrl.href
            : "";
    } catch {
        return "";
    }
},
```

ただし、ブラウザ側の安全対策だけでは不十分です。最終的な検証はLaravelの`GenerateResumeRequest`でも行います。

## 16. Laravelとの役割分担

### Alpine.jsの担当

- 入力中の状態管理
- 入力欄の追加・削除
- 選択内容に応じた表示切り替え
- 候補リストの切り替え
- ライブプレビュー
- 配列形式の`name`属性生成

### Laravelの担当

- 初期画面の表示
- JSON候補データの読み込み
- CSRF保護
- サーバー側バリデーション
- 送信後のプレビュー
- 将来のDOCX/PDF生成
- 将来のAI要約API連携

Alpine.jsで表示上のチェックをしていても、サーバー側の検証は必須です。利用者はJavaScriptを無効化したり、リクエストを直接送ったりできるためです。

## 17. Alpine.jsとBladeの違い

BladeはサーバーでHTMLを生成します。

```blade
@if ($user)
    <p>{{ $user->name }}</p>
@endif
```

Alpine.jsはブラウザで状態を変更し、画面を更新します。

```blade
<div x-data="{ open: false }">
    <button @click="open = !open">表示切替</button>
    <div x-show="open">表示される内容</div>
</div>
```

今回のアプリでは、初期データや送信後の帳票はBlade、入力中の追加・削除やライブプレビューはAlpine.jsという分担です。

## 18. よくある間違い

### `x-data`の外で状態を使う

```blade
<div>
    <span x-text="resume.full_name"></span>
</div>
```

`x-data`の外側では`resume`が定義されていないため動作しません。

### 追加ボタンに`type="button"`を付け忘れる

フォーム内の`button`は、デフォルトで送信ボタンとして動く場合があります。

```blade
<button type="button" @click="addProject">追加</button>
```

入力欄の追加や削除ボタンには`type="button"`を付けます。

### `x-for`に`:key`を付けない

繰り返し要素の追加・削除で、表示と状態の対応が不安定になる可能性があります。

```blade
<template x-for="item in items" :key="item.id">
```

### `x-html`へ未エスケープの入力を入れる

XSSにつながるため、ユーザー入力は必ず`escape()`を通します。

### Alpine.jsだけでバリデーションを完了したと思う

ブラウザ側の処理は信頼境界ではありません。Laravel側でも同じ入力を検証します。

## 19. 学習の進め方

### Step 1: 単純な表示切り替え

次のコードを読んで、ボタンを押したときの動作を確認する。

```html
<div x-data="{ open: false }">
  <button type="button" @click="open = !open">切り替え</button>
  <p x-show="open">表示されました</p>
</div>
```

### Step 2: 入力値の同期

```html
<div x-data="{ name: '' }">
  <input x-model="name" />
  <p x-text="name"></p>
</div>
```

### Step 3: 配列の追加・削除

```html
<div x-data="{ items: ['A'] }">
  <button type="button" @click="items.push('新しい項目')">追加</button>
  <template x-for="(item, index) in items" :key="index">
    <p>
      <span x-text="item"></span>
      <button type="button" @click="items.splice(index, 1)">削除</button>
    </p>
  </template>
</div>
```

### Step 4: 現在のフォームを読む

次の順番で読むと理解しやすい。

1. `create.blade.php`の`x-data`
2. `resume-form.js`の`resume`初期値
3. `x-model`がどのプロパティへ接続されているか
4. `@click`がどのメソッドを呼ぶか
5. `x-for`がどの配列を繰り返しているか
6. `renderPreview()`が入力データをどうHTMLへ変換するか
7. Laravelの`GenerateResumeRequest`が何を検証するか

## 20. 次に学ぶとよい機能

現在のアプリの次の実装に役立つAlpine.jsの機能:

- `x-init`: 初期化処理
- `x-effect`: 状態変更に応じた処理
- `x-ref`: DOM要素への参照
- `$dispatch`: コンポーネント間のイベント通知
- `$watch`: 特定の値の変更監視
- `x-transition`: 表示切り替えのアニメーション
- `Alpine.data()`: 再利用可能なコンポーネント定義
- `Alpine.store()`: 複数コンポーネントで共有する状態

ただし、今のアプリではまず`x-data`、`x-model`、`x-for`、`x-show`、`@click`、`:name`を理解すれば十分です。

## 21. まとめ

このアプリのAlpine.jsは、次の考え方で動いています。

```text
x-data
  ↓
フォーム状態 resume を作る
  ↓
x-model で入力欄と状態を同期
  ↓
@click で配列を追加・削除
  ↓
x-for で配列の数だけ入力欄を表示
  ↓
x-html で状態からプレビューを生成
  ↓
フォーム送信時はLaravelへ配列として渡す
```

Alpine.jsを理解する最初のポイントは、HTMLに書かれた属性とJavaScriptの状態がどのようにつながっているかを見ることです。現在のプロジェクトでは、`resume`オブジェクトが画面の中心であり、入力欄、追加・削除処理、プレビューがすべてそこを参照しています。
