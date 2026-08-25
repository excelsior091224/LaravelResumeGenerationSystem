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
    business_overview: "",
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
const blankResume = () => ({
    full_name: "",
    as_of_date: new Date().toISOString().slice(0, 10),
    links: [blankLink()],
    summary: "",
    specialty: "",
    self_pr: "",
    considerations: "",
    skills: [blankSkill()],
    companies: [blankCompany()],
    certifications: [blankCertification()],
});
const draftStorageKey = "resume-foundry-draft-v1";

window.resumeForm = (skillData, roleData) => ({
    // PHPから受け取ったカテゴリ別スキルを候補表示用の配列へ変換する。
    categories: Object.entries(skillData).map(([key, category]) => ({
        key,
        ...category,
    })),
    roleGroups: roleData,
    aiConsent: false,
    summaryLoading: false,
    summaryError: "",
    resume: blankResume(),
    init() {
        const draft = this.loadDraft();
        if (draft) {
            this.resume = draft;
        }

        this.$watch("resume", () => this.saveDraft());
    },
    addSkill() {
        this.resume.skills.push(blankSkill());
    },
    skillOptions(categoryLabel) {
        return (
            this.categories.find((category) => category.label === categoryLabel)
                ?.skills || []
        );
    },
    selectSkillCategory(skill) {
        skill.name = "";
        if (skill.category === "担当業務") {
            skill.level = "";
        }
    },
    normalizeSkill(skill) {
        const normalized = {
            ...blankSkill(),
            ...skill,
            id: skill.id || crypto.randomUUID(),
        };

        if (
            normalized.category === "担当業務" &&
            normalized.name &&
            !this.skillOptions(normalized.category).includes(normalized.name)
        ) {
            normalized.name = "";
        }
        if (normalized.category === "担当業務") {
            normalized.level = "";
        }

        return normalized;
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
    loadDraft() {
        try {
            const draft = JSON.parse(localStorage.getItem(draftStorageKey));
            if (!draft || typeof draft !== "object") {
                return null;
            }

            return {
                ...blankResume(),
                ...draft,
                links:
                    Array.isArray(draft.links) && draft.links.length
                        ? draft.links.map((link) => ({
                              ...blankLink(),
                              ...link,
                              id: link.id || crypto.randomUUID(),
                          }))
                        : [blankLink()],
                skills:
                    Array.isArray(draft.skills) && draft.skills.length
                        ? draft.skills.map((skill) =>
                              this.normalizeSkill(skill),
                          )
                        : [blankSkill()],
                companies:
                    Array.isArray(draft.companies) && draft.companies.length
                        ? draft.companies.map((company) => ({
                              ...blankCompany(),
                              ...company,
                              id: company.id || crypto.randomUUID(),
                              projects:
                                  Array.isArray(company.projects) &&
                                  company.projects.length
                                      ? company.projects.map((project) => ({
                                            ...blankProject(),
                                            ...project,
                                            id:
                                                project.id ||
                                                crypto.randomUUID(),
                                        }))
                                      : [blankProject()],
                          }))
                        : [blankCompany()],
                certifications:
                    Array.isArray(draft.certifications) &&
                    draft.certifications.length
                        ? draft.certifications.map((certification) => ({
                              ...blankCertification(),
                              ...certification,
                              id: certification.id || crypto.randomUUID(),
                          }))
                        : [blankCertification()],
            };
        } catch {
            return null;
        }
    },
    saveDraft() {
        try {
            localStorage.setItem(draftStorageKey, JSON.stringify(this.resume));
        } catch {
            // ブラウザの保存領域が使えない場合も、入力自体は継続できる。
        }
    },
    clearDraft() {
        if (
            !window.confirm("この端末に保存した入力中の下書きを削除しますか？")
        ) {
            return;
        }

        localStorage.removeItem(draftStorageKey);
        this.resume = blankResume();
        this.aiConsent = false;
        this.summaryError = "";
    },
    download(action) {
        const form = this.$refs.resumeForm;

        if (!form.reportValidity()) {
            return;
        }

        form.action = action;
        form.submit();
    },
    hasAnyValue(item, fields) {
        return fields.some((field) => {
            const value = item[field];
            return Array.isArray(value)
                ? value.length > 0
                : String(value || "").trim() !== "";
        });
    },
    summaryCareerData() {
        const companies = this.resume.companies
            .map((company) => {
                const projects = company.projects
                    .filter((project) =>
                        this.hasAnyValue(project, [
                            "name",
                            "period_from",
                            "period_to",
                            "description",
                            "role",
                            "role_custom",
                            "team",
                            "processes",
                            "technologies",
                        ]),
                    )
                    .map(
                        ({
                            name,
                            period_from,
                            period_to,
                            description,
                            role,
                            role_custom,
                            team,
                            processes,
                            technologies,
                        }) => ({
                            name,
                            period_from,
                            period_to,
                            description,
                            role,
                            role_custom,
                            team,
                            processes,
                            technologies,
                        }),
                    );

                return {
                    name: company.name,
                    employment_type: company.employment_type,
                    employment_type_custom: company.employment_type_custom,
                    period_from: company.period_from,
                    period_to: company.period_to,
                    industry: company.industry,
                    business_overview: company.business_overview,
                    projects,
                };
            })
            .filter((company) =>
                this.hasAnyValue(company, [
                    "name",
                    "employment_type",
                    "employment_type_custom",
                    "period_from",
                    "period_to",
                    "industry",
                    "business_overview",
                    "projects",
                ]),
            );
        const skills = this.resume.skills.filter((skill) =>
            this.hasAnyValue(skill, [
                "category",
                "name",
                "years",
                "level",
                "note",
            ]),
        );
        const certifications = this.resume.certifications.filter(
            (certification) =>
                this.hasAnyValue(certification, ["date", "name"]),
        );

        return { companies, skills, certifications };
    },
    async generateSummary() {
        if (!this.aiConsent || this.summaryLoading) {
            return;
        }

        this.summaryLoading = true;
        this.summaryError = "";

        try {
            const response = await fetch("/resume/summary", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
                body: JSON.stringify({
                    ai_consent: true,
                    ...this.summaryCareerData(),
                }),
            });
            const payload = await response.json();

            if (!response.ok || !payload.summary) {
                throw new Error(
                    payload.message || "職務要約を生成できませんでした。",
                );
            }

            this.resume.summary = payload.summary;
        } catch (error) {
            this.summaryError =
                error.message || "職務要約を生成できませんでした。";
        } finally {
            this.summaryLoading = false;
        }
    },
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
    displayMonth(value) {
        return /^\d{4}-\d{2}$/.test(value || "")
            ? `${value.replace("-", "年")}月`
            : value || "";
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
            .filter((company) =>
                this.hasAnyValue(company, [
                    "name",
                    "employment_type",
                    "period_from",
                    "period_to",
                    "industry",
                    "business_overview",
                    "projects",
                ]),
            )
            .map((company) => {
                const projects = company.projects
                    .filter((project) =>
                        this.hasAnyValue(project, [
                            "name",
                            "period_from",
                            "period_to",
                            "description",
                            "role",
                            "role_custom",
                            "team",
                            "processes",
                            "technologies",
                        ]),
                    )
                    .sort((a, b) =>
                        (b.period_from || "").localeCompare(
                            a.period_from || "",
                        ),
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
                return `<div class="company-block"><p class="company-title">勤務先：${this.escape(this.displayCompanyName(company))}（${this.escape(companyPeriod)}）</p>${companyMeta ? `<p class="project-detail">${this.escape(companyMeta)}</p>` : ""}${company.business_overview ? `<p class="project-detail"><b>【業務概要】</b><br>${this.lines(company.business_overview)}</p>` : ""}${projects
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
                    `<li>${this.escape(this.displayMonth(item.date))}　${this.escape(item.name)}</li>`,
            )
            .join("");
        const links = this.resume.links
            .filter((link) => link.url && this.safeLinkUrl(link.url))
            .map((link) => {
                const safeUrl = this.safeLinkUrl(link.url);
                return `<li><span>${this.escape(this.displayLinkType(link))}：</span>${this.escape(safeUrl)}</li>`;
            })
            .join("");
        return `<div class="paper-header"><h2>職務経歴書</h2><div class="paper-meta">${this.escape(date)}<br><b>氏名：${this.escape(r.full_name || "")}</b></div></div><div class="paper-section"><h3>■ 職務要約</h3><p class="summary-text">${this.lines(r.summary || "")}</p></div><div class="paper-section"><h3>■ 得意業務</h3><p>・ ${this.escape(r.specialty || "")}</p></div><div class="paper-section"><h3>■ 技術系アカウント・ポートフォリオ</h3>${links ? `<ul>${links}</ul>` : '<p class="empty-note">技術系アカウントやポートフォリオを入力してください</p>'}</div><div class="paper-section"><h3>■ PCスキル / テクニカルスキル</h3><table class="paper-table"><thead><tr><th>カテゴリ</th><th>スキル</th><th>経験年数</th><th>経験区分</th><th>備考</th></tr></thead><tbody>${skillRows}</tbody></table></div><div class="paper-section"><h3>■ 職務経歴</h3>${projects || '<p class="empty-note">所属企業とプロジェクトを入力してください</p>'}</div><div class="paper-section"><h3>■ 資格</h3>${certifications ? `<ul>${certifications}</ul>` : '<p class="empty-note">資格を入力してください</p>'}</div><div class="paper-section"><h3>■ 自己PR</h3><p>${this.lines(r.self_pr || "")}</p></div>${r.considerations ? `<div class="paper-section"><h3>■ 配慮事項</h3><p>${this.lines(r.considerations)}</p></div>` : ""}<div class="paper-closing"><p class="paper-closing-end">以上</p><p class="paper-closing-message">是非、面接の機会をいただければと思います。何卒よろしくお願いいたします。</p></div>`;
    },
});

window.Alpine = Alpine;
Alpine.start();
