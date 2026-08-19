import Alpine from "alpinejs";

// 追加ボタンから生成する繰り返し入力の初期値。
const blankProject = () => ({
    id: crypto.randomUUID(),
    period_from: "",
    period_to: "",
    is_current: false,
    name: "",
    description: "",
    role: "",
    role_custom: "",
    team: "",
    processes: "",
    technologies: "",
});
const blankCompany = () => ({
    id: crypto.randomUUID(),
    name: "",
    employment_type: "",
    employment_type_custom: "",
    period_from: "",
    period_to: "",
    is_current: false,
    industry: "",
    established: "",
    capital: "",
    employees: "",
    projects: [blankProject()],
});
const blankSkill = () => ({
    id: crypto.randomUUID(),
    category: "",
    name: "",
    years: "",
    level: "",
    note: "",
});
const blankCertification = () => ({
    id: crypto.randomUUID(),
    date: "",
    name: "",
});
const blankLink = () => ({
    id: crypto.randomUUID(),
    type: "",
    type_custom: "",
    url: "",
});

window.resumeForm = (skillData, roleData) => ({
    // PHPから受け取ったカテゴリ別スキルを候補表示用の配列へ変換する。
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
    addSkill() {
        this.resume.skills.push(blankSkill());
    },
    addCompany() {
        this.resume.companies.push(blankCompany());
    },
    addProject(companyIndex) {
        this.resume.companies[companyIndex].projects.push(blankProject());
    },
    removeCompany(index) {
        this.resume.companies.splice(index, 1);
    },
    removeProject(companyIndex, projectIndex) {
        this.resume.companies[companyIndex].projects.splice(projectIndex, 1);
    },
    addCertification() {
        this.resume.certifications.push(blankCertification());
    },
    addLink() {
        this.resume.links.push(blankLink());
    },
    removeItem(collection, index) {
        this.resume[collection].splice(index, 1);
    },
    syncForm() {},
    // ユーザー入力をHTMLへ埋め込む前にエスケープし、XSSを防ぐ。
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
    lines(value) {
        return this.escape(value).replace(/\n/g, "<br>");
    },
    displayRole(project) {
        return project.role === "その他" ? project.role_custom : project.role;
    },
    displayCompanyName(company) {
        return (
            company.name ||
            (company.employment_type === "フリーランス"
                ? "フリーランス"
                : "所属企業名未入力")
        );
    },
    displayLinkType(link) {
        return link.type === "その他" ? link.type_custom : link.type;
    },
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
    renderPreview() {
        // 入力中の状態から帳票レイアウト用HTMLを組み立てる。
        const r = this.resume;
        const date = r.as_of_date || "";
        // 入力順に左右されないようカテゴリごとにまとめ、同じカテゴリの行を連続させる。
        const skillGroups = this.resume.skills
            .filter(
                (skill) =>
                    skill.name ||
                    skill.category ||
                    skill.years ||
                    skill.level ||
                    skill.note,
            )
            .reduce((groups, skill) => {
                const category = skill.category || "未分類";
                groups[category] ??= [];
                groups[category].push(skill);
                return groups;
            }, {});
        const skillRows =
            Object.entries(skillGroups)
                .map(([category, skills]) =>
                    skills
                        .map(
                            (skill, index) =>
                                `<tr>${index === 0 ? `<td rowspan="${skills.length}">${this.escape(category)}</td>` : ""}<td>${this.escape(skill.name || "")}</td><td>${this.escape(skill.years || "")}</td><td>${this.escape(skill.level || "")}</td><td>${this.escape(skill.note || "")}</td></tr>`,
                        )
                        .join(""),
                )
                .join("") ||
            '<tr><td colspan="5" class="empty-note">スキルを入力してください</td></tr>';
        // 企業を新しい在籍開始年月順に並べ、企業内の案件も開始年月順に並べる。
        const companies = [...this.resume.companies].sort((a, b) =>
            (b.period_from || "").localeCompare(a.period_from || ""),
        );
        const projects = companies
            .map((company) => {
                const projects = [...company.projects].sort((a, b) =>
                    (b.period_from || "").localeCompare(a.period_from || ""),
                );
                const companyPeriod = [company.period_from, company.period_to]
                    .filter(Boolean)
                    .join("〜");
                const companyMeta = [
                    company.employment_type === "その他"
                        ? company.employment_type_custom
                        : company.employment_type,
                    company.industry,
                    company.established && `設立：${company.established}`,
                    company.capital && `資本金：${company.capital}`,
                    company.employees && `従業員数：${company.employees}`,
                ]
                    .filter(Boolean)
                    .join(" / ");
                return `<div class="company-block"><p class="company-title">勤務先：${this.escape(this.displayCompanyName(company))}（${this.escape(companyPeriod)}）</p>${companyMeta ? `<p class="project-detail">${this.escape(companyMeta)}</p>` : ""}${projects
                    .map((project) => {
                        const projectPeriod = [
                            project.period_from,
                            project.period_to,
                        ]
                            .filter(Boolean)
                            .join("〜");
                        return `<div class="project-block"><p class="project-title">■ ${this.escape(project.name || "")}（${this.escape(projectPeriod)}）</p><p class="project-detail">${this.lines(project.description || "")}</p><p class="project-detail"><b>【担当工程】</b><br>${this.lines(project.processes || "")}</p><p class="project-detail"><b>【使用技術・DB・OS】</b><br>${this.lines(project.technologies || "")}</p><p class="project-detail"><b>【組織・役割】</b><br>${this.lines(this.displayRole(project))} / ${this.lines(project.team)}</p></div>`;
                    })
                    .join("")}</div>`;
            })
            .join("");
        const certifications = this.resume.certifications
            .filter((item) => item.name)
            .map(
                (item) =>
                    `<li>${this.escape(item.date)}　${this.escape(item.name)}</li>`,
            )
            .join("");
        const links = this.resume.links
            .filter((link) => link.url && this.safeLinkUrl(link.url))
            .map((link) => {
                const safeUrl = this.safeLinkUrl(link.url);
                return `<li><span>${this.escape(this.displayLinkType(link))}：</span>${this.escape(safeUrl)}</li>`;
            })
            .join("");
        return `<div class="paper-header"><h2>職務経歴書</h2><div class="paper-meta">${this.escape(date)}<br><b>氏名：${this.escape(r.full_name || "")}</b></div></div><div class="paper-section"><h3>■ 職務要約</h3><p class="summary-text">${this.lines(r.summary || "")}</p></div><div class="paper-section"><h3>■ 得意業務</h3><p>・ ${this.escape(r.specialty || "")}</p></div><div class="paper-section"><h3>■ 技術系アカウント・ポートフォリオ</h3>${links ? `<ul>${links}</ul>` : '<p class="empty-note">技術系アカウントやポートフォリオを入力してください</p>'}</div><div class="paper-section"><h3>■ PCスキル / テクニカルスキル</h3><table class="paper-table"><thead><tr><th>カテゴリ</th><th>スキル</th><th>経験年数</th><th>経験区分</th><th>備考</th></tr></thead><tbody>${skillRows}</tbody></table></div><div class="paper-section"><h3>■ 職務経歴</h3>${projects || '<p class="empty-note">所属企業とプロジェクトを入力してください</p>'}</div><div class="paper-section"><h3>■ 資格</h3>${certifications ? `<ul>${certifications}</ul>` : '<p class="empty-note">資格を入力してください</p>'}</div><div class="paper-section"><h3>■ 自己PR</h3><p>${this.lines(r.self_pr || "")}</p></div>`;
    },
});

window.Alpine = Alpine;
Alpine.start();
