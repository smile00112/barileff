# Руководство по разработке Skills для Claude Code (Edition 2026)

Это техническая спецификация и руководство по созданию Skills — модульных расширений возможностей Claude Code. Документ основан на [официальной документации](https://code.claude.com/docs/en/skills) и практическом опыте.

---

## Оглавление

1. [Философия Skills](#философия-skills)
2. [Как работают Skills](#как-работают-skills)
3. [Где хранятся Skills](#где-хранятся-skills)
4. [Анатомия Skill](#анатомия-skill)
5. [Метаданные SKILL.md](#метаданные-skillmd)
6. [Прогрессивное раскрытие](#прогрессивное-раскрытие)
7. [Ограничение инструментов](#ограничение-инструментов)
8. [Изолированный контекст](#изолированный-контекст)
9. [Хуки](#хуки)
10. [Управление видимостью](#управление-видимостью)
11. [Примеры Skills](#примеры-skills)
12. [Troubleshooting](#troubleshooting)
13. [Best Practices](#best-practices)

---

## Философия Skills

### Model-Invoked против Static Context

В отличие от традиционных подходов (как `.cursorrules`, которые загружаются всегда), **Skills** в Claude Code работают по принципу **Model-Invoked** (вызывается моделью).

*   **Discovery Phase:** При старте Claude видит только *метаданные* Skills (название, описание). Это минимизирует расход токенов.
*   **Activation Phase:** Когда интент пользователя совпадает с описанием Skill, Claude загружает полную инструкцию в активный контекст.

**Цель:** Создавать изолированные, узкоспециализированные модули поведения, которые активируются только тогда, когда они нужны.

### Skills против других механизмов

| Механизм | Когда использовать | Когда запускается |
|----------|-------------------|-------------------|
| **Skills** | Специализированные знания (например, "code review по нашим стандартам") | Claude выбирает автоматически |
| **Slash commands** | Переиспользуемые промпты (например, `/deploy staging`) | Вы вызываете через `/command` |
| **CLAUDE.md** | Инструкции для всего проекта (например, "используй TypeScript strict mode") | Загружается в каждую сессию |
| **Subagents** | Делегирование задач в отдельный контекст | Claude делегирует или вы вызываете явно |
| **MCP servers** | Подключение к внешним инструментам | Claude вызывает MCP tools |

---

## Как работают Skills

Skills автоматически вызываются моделью на основе вашего запроса. Вам не нужно явно вызывать Skill.

Когда вы отправляете запрос, Claude следует этим шагам:

1. **Анализ запроса** — Claude определяет интент и сравнивает с описаниями доступных Skills
2. **Выбор Skill** — Если запрос совпадает с описанием, Claude загружает полный Skill
3. **Применение** — Claude следует инструкциям из SKILL.md
4. **Результат** — Ответ генерируется с учетом специализации Skill

---

## Где хранятся Skills

Место хранения определяет, кто может использовать Skill:

| Тип | Путь | Применяется к |
|-----|-----|---------------|
| **Enterprise** | См. managed settings | Все пользователи организации |
| **Personal** | `~/.claude/skills/` | Только вы, во всех проектах |
| **Project** | `.claude/skills/` | Все, кто работает в репозитории |
| **Plugin** | В комплекте плагина | Все с установленным плагином |

Приоритет при совпадении имён: Enterprise → Personal → Project → Plugin.

---

## Анатомия Skill

Skill — это **папка-модуль** с файлом `SKILL.md`.

### Структура директории

```
.claude/
  skills/
    <skill-slug>/           # Например: test-engineer
      SKILL.md              # ЕДИНЫЙ ОБЯЗАТЕЛЬНЫЙ файл
      reference.md          # Детальная документация (опционально)
      examples.md           # Примеры использования (опционально)
      templates/            # Шаблоны кода (опционально)
        component.tsx.hbs
      scripts/              # Вспомогательные скрипты (опционально)
        validate.py
```

### Формат SKILL.md

Файл состоит из двух частей: **YAML Frontmatter** (метаданные для роутера) и **Markdown Body** (инструкции для "мозга" агента).

```markdown
---
name: "your-skill-name"
description: "Brief description of what this skill does and when to use it"
allowed-tools:
  - Read
  - Grep
---

# Your Skill Name

## Instructions
Provide clear, step-by-step guidance for Claude.

## Examples
Show concrete examples of using this Skill.
```

---

## Метаданные SKILL.md

Frontmatter — это критически важная часть для **обнаружения** Skill. Если описание плохое, Claude никогда не вызовет этот Skill.

### Обязательные поля

| Поле | Описание |
|------|----------|
| `name` | Имя Skill. Только lowercase, цифры и дефисы (max 64 символа). Должно совпадать с именем директории |
| `description` | Описание (max 1024 символа). Claude использует его для решения, когда применить Skill |

### Опциональные поля

| Поле | Описание |
|------|----------|
| `allowed-tools` | Инструменты, которые Claude может использовать без разрешения. См. [Ограничение инструментов](#ограничение-инструментов) |
| `model` | Модель для использования (например, `claude-sonnet-4-20250514`). По умолчанию — модель разговора |
| `context` | Установите `fork` для запуска в изолированном sub-agent контексте. См. [Изолированный контекст](#изолированный-контекст) |
| `agent` | Агент для `context: fork` (например, `Explore`, `Plan`, `general-purpose`) |
| `hooks` | Хуки для жизненного цикла Skill. См. [Хуки](#хуки) |
| `user-invocable` | Показывать ли в меню slash-команд (по умолчанию `true`). См. [Управление видимостью](#управление-видимостью) |

---

## Прогрессивное раскрытие

Skills разделяют контекстное окно с историей разговора и другими Skills. Для сохранения фокуса используйте **progressive disclosure**: основную информацию в `SKILL.md`, детальные справочные материалы — в отдельных файлах.

Claude загружает дополнительные файлы только при необходимости.

### Пример многофайлового Skill

```
pdf-processing/
├── SKILL.md              # Обзор и быстрый старт
├── FORMS.md              # Маппинг полей форм
├── REFERENCE.md          # API детали для pypdf и pdfplumber
└── scripts/
    ├── fill_form.py      # Утилита заполнения форм
    └── validate.py       # Проверка PDF
```

### Связывание файлов в SKILL.md

```markdown
---
name: pdf-processing
description: Extract text, fill forms, merge PDFs
---

# PDF Processing

## Quick start

Extract text:
\`\`\`python
import pdfplumber
with pdfplumber.open("doc.pdf") as pdf:
    text = pdf.pages[0].extract_text()
\`\`\`

For form filling, see [FORMS.md](FORMS.md).
For detailed API reference, see [REFERENCE.md](REFERENCE.md).

## Utility scripts

To validate input files, run:
\`\`\`bash
python scripts/validate.py input.pdf
\`\`\`
```

---

## Ограничение инструментов

Используйте поле `allowed-tools` для ограничения инструментов, которые Claude может использовать когда Skill активен.

### Формат

```yaml
---
name: reading-files-safely
description: Read files without making changes
allowed-tools: Read, Grep, Glob
---
```

Или YAML-список:

```yaml
---
name: reading-files-safely
description: Read files without making changes
allowed-tools:
  - Read
  - Grep
  - Glob
---
```

### Когда использовать

- Read-only Skills, которые не должны модифицировать файлы
- Skills с ограниченной областью: только анализ данных, без записи
- Security-sensitive workflows

---

## Изолированный контекст

Используйте `context: fork` для запуска Skill в изолированном sub-agent контексте с собственной историей разговора.

```yaml
---
name: code-analysis
description: Analyze code quality and generate detailed reports
context: fork
agent: general-purpose
---
```

Поле `agent` определяет тип агента:
- `Explore` — для исследования кодовой базы
- `Plan` — для планирования
- `general-purpose` — по умолчанию
- Custom agent из `.claude/agents/`

---

## Хуки

Skills могут определять хуки, которые запускаются в течение жизненного цикла Skill.

```yaml
---
name: secure-operations
description: Perform operations with additional security checks
hooks:
  PreToolUse:
    - matcher: "Bash"
      hooks:
        - type: command
          command: "./scripts/security-check.sh $TOOL_INPUT"
          once: true
---
```

Опция `once: true` запускает хук только один раз за сессию.

Типы событий:
- `PreToolUse` — перед использованием инструмента
- `PostToolUse` — после использования инструмента
- `Stop` — при остановке Skill

---

## Управление видимостью

Skills могут вызываться тремя способами:

1. **Manual invocation** — Вы вводите `/skill-name`
2. **Programmatic invocation** — Claude вызывает через `Skill` tool
3. **Automatic discovery** — Claude загружает Skill когда он релевантен

Поле `user-invocable` контролирует только manual invocation:

| Настройка | Slash меню | `Skill` tool | Auto-discovery | Use case |
|-----------|------------|--------------|----------------|----------|
| `user-invocable: true` (default) | Виден | Разрешён | Да | Skills для прямого вызова |
| `user-invocable: false` | Скрыт | Разрешён | Да | Skills для Claude, не для пользователей |

### Пример: только для модели

```yaml
---
name: internal-review-standards
description: Apply internal code review standards when reviewing pull requests
user-invocable: false
---
```

---

## Примеры Skills

### Простой Skill (один файл)

```
commit-helper/
└── SKILL.md
```

```markdown
---
name: generating-commit-messages
description: Generates clear commit messages from git diffs. Use when writing commit messages or reviewing staged changes.
---

# Generating Commit Messages

## Instructions

1. Run `git diff --staged` to see changes
2. Suggest a commit message with:
   - Summary under 50 characters
   - Detailed description
   - Affected components

## Best practices

- Use present tense
- Explain what and why, not how
```

### Сложный Skill (много файлов)

```
test-engineer/
├── SKILL.md
├── TESTING_PATTERNS.md
├── MOCKING_GUIDE.md
└── scripts/
    └── generate-test.js
```

```markdown
---
name: test-engineer
description: Generates, runs, and fixes unit tests for TypeScript components. Use when writing tests, fixing failing tests, or checking coverage.
allowed-tools: Read, Write, Bash, Glob, Grep
---

# Test Engineering Protocol

## Overview

You are an expert QA Engineer. Your goal is ensure 100% functional coverage for the target file.

For testing patterns, see [TESTING_PATTERNS.md](TESTING_PATTERNS.md).
For mocking guide, see [MOCKING_GUIDE.md](MOCKING_GUIDE.md).

## Execution Steps

1. **Identify target** — Find the file under test
2. **Check existing tests** — Search in `__tests__` or `*.test.ts` files
3. **Generate test** — Use the test generator script or create manually
4. **Run tests** — Execute test runner and analyze results
5. **Fix failures** — Debug and fix until all tests pass

## Quality Criteria

- 100% coverage for modified code
- All tests pass
- No mocked data in test files (use factories)
```

---

## Troubleshooting

### Skill не срабатывает

**Проблема:** Claude не использует Skill

**Решение:** Проверьте поле `description`. Оно должно отвечать на два вопроса:
1. Что делает этот Skill?
2. Когда его использовать?

❌ Плохо: `Helps with documents`

✅ Хорошо: `Extract text and tables from PDF files, fill forms, merge documents. Use when working with PDF files or when the user mentions PDFs, forms, or document extraction.`

### Skill не загружается

**Проверьте путь:**

| Тип | Путь |
|-----|------|
| Personal | `~/.claude/skills/my-skill/SKILL.md` |
| Project | `.claude/skills/my-skill/SKILL.md` |

**Проверьте YAML синтаксис:**
- Frontmatter должен начинаться с `---` на строке 1 (без пустых строк перед)
- Используйте пробелы для отступа, не табы
- Заканчивайте frontmatter `---` перед Markdown контентом

**Запустите debug режим:**
```bash
claude --debug
```

### Несколько Skills конфликтуют

Если Claude путает похожие Skills, сделайте описания более специфичными с уникальными триггерными терминами.

---

## Best Practices

### 1. Написание описания

Хорошее описание:

✅ Указывает конкретные действия (extract, fill, merge)
✅ Включает ключевые слова пользователей (PDF, forms, document extraction)
✅ Объясняет когда использовать (Use when...)

### 2. Структура SKILL.md

```markdown
---
name: skill-name
description: Clear description with trigger terms
---

# Human Readable Name

## Overview
Brief summary of what this skill does.

## Quick Start
Minimal steps to get started.

## Detailed Instructions
Step-by-step guidance.

## Examples
Concrete input/output examples.

## Troubleshooting
Common issues and solutions.

## Related Resources
Links to supporting files.
```

### 3. Размер Skill

- **Compact:** До 150 строк для роутеров и быстрых проверок
- **Standard:** 150-600 строк для типичных workflows
- **Large:** 600+ строк для комплексных систем (используйте progressive disclosure)

### 4. Именование

- Используйте `kebab-case` для имён директорий
- Имя должно совпадать с полем `name` в SKILL.md
- Используйте описательные имена: `test-engineer` — хорошо, `helper` — плохо

### 5. Дистрибуция

**Project Skills:** Коммитьте `.claude/skills/` в version control

**Plugins:** Создайте `skills/` директорию в плагине

**Managed:** Администраторы могут деплоить Skills organization-wide

---

## Дополнительные ресурсы

- **Официальная документация:** [Agent Skills - Claude Code Docs](https://code.claude.com/docs/en/skills)
- **Практический гайд:** [Claude Skills and CLAUDE.md: a practical 2026 guide](https://www.gend.co/blog/claude-skills-claude-md-guide)
- **GitHub репозиторий:** [levnikolaevich/claude-code-skills](https://github.com/levnikolaevich/claude-code-skills)

---

## Лицензия

MIT License — см. [LICENSE](../../LICENSE)
