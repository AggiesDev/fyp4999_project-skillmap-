# FYP2 Demo Data

This repository includes a repeatable demo data seed script:

```bash
/Applications/XAMPP/xamppfiles/bin/php database/seed_fyp2_demo_data.php
```

The script does not add new application features. It prepares realistic demo accounts and data for showing the existing FYP system, while still keeping known match percentages that can support validation evidence.

## Login Accounts

All seeded passwords follow `username@123`.

| Role | Usernames |
| --- | --- |
| Student | `student01` to `student15` |
| Staff | `staff01` to `staff05` |
| Lecturer | `lecturer01` to `lecturer03` |

Example: username `student01`, password `student01@123`.

## Known Demo Match Cases

Use these demo accounts to manually verify the rule-based matching calculation:

| Student | Target role | Expected match | Purpose |
| --- | --- | ---: | --- |
| `student01` | Web Developer | 100.00% | All career requirements met |
| `student02` | Web Developer | 0.00% | No career requirements met |
| `student03` | Web Developer | 72.00% | Most skills close to required level |
| `student04` | Web Developer | 68.00% | Mixed Have, Partial, and Missing |
| `student05` | Student Club President | 100.00% | All leadership requirements met |
| `student06` | Student Club President | 0.00% | No leadership requirements met |
| `student07` | Student Club President | 66.67% | Mixed leadership gap case |
| `student08` | Data Analyst | 78.95% | Career-role analytics variety |
| `student09` | IT Support | 86.67% | Career-role analytics variety |
| `student10` | Software Engineer | 81.82% | Career-role analytics variety |
| `student11` | Cybersecurity Analyst | 73.68% | Career-role analytics variety |
| `student12` | Project Manager | 83.33% | Leadership-role analytics variety |
| `student13` | UI/UX Designer | 76.47% | Cross-category gap case |
| `student14` | Database Administrator | 61.11% | Lower-readiness career case |
| `student15` | Business Analyst | 94.12% | High-readiness career case |

Manual formula:

```text
match percentage = sum(min(student rating, required rating)) / sum(required rating) * 100
```

Status boundary rules:

| Condition | Expected status |
| --- | --- |
| `student_rating >= required_rating` | Have |
| `required_rating - student_rating <= 1` | Partial |
| `required_rating - student_rating > 1` | Missing |

## Demo And Evidence Coverage

The seeded data supports demo presentation and these supervisor feedback items:

| Area | Evidence to verify |
| --- | --- |
| Career-role gap analysis | `student01` to `student04`, `student08` to `student11`, `student13` to `student15` |
| Leadership-role gap analysis | `student05`, `student06`, `student07`, `student12` |
| Boundary conditions | 100%, 0%, exactly one level short, mixed gap statuses |
| Roadmap prioritisation | Missing skills appear as Priority 1, Partial skills as Priority 2 |
| Roadmap resources | Seeded missing/partial skills have platform, URL, free/paid label, and duration hours |
| Analytics dashboard | Programme readiness, top missing skills, gap status summary, trend, and latest low-score students |
| Role permissions | Use student, staff, lecturer, and admin/demo accounts to test direct URL access |

## Suggested Direct URL Permission Checks

Log in as each role and manually request these URLs:

| URL | Student | Lecturer | Staff | Admin |
| --- | --- | --- | --- | --- |
| `/fyp_skillmapsystem/users/dashboard.php` | Allow | Deny | Deny | Deny |
| `/fyp_skillmapsystem/users/gap_analysis.php` | Allow | Deny | Deny | Deny |
| `/fyp_skillmapsystem/admin/analytics.php` | Deny | Allow if permission enabled | Allow if permission enabled | Allow |
| `/fyp_skillmapsystem/admin/manage_users.php` | Deny | Deny unless explicitly granted | Deny unless explicitly granted | Allow |
| `/fyp_skillmapsystem/admin/permissions.php` | Deny | Deny | Deny | Allow |

## SUS And Effectiveness Evidence

For SUS, collect the 10-item SUS questionnaire after UAT and calculate:

```text
odd items: score - 1
even items: 5 - score
SUS score = sum(adjusted scores) * 2.5
```

For system effectiveness, report task completion rate and calculation accuracy:

```text
task completion rate = completed UAT tasks / attempted UAT tasks * 100
match calculation accuracy = correctly verified known cases / total known cases * 100
roadmap evidence accuracy = roadmap cases showing priority + resource + timeline / roadmap cases checked * 100
analytics accuracy = correctly verified dashboard aggregates / aggregates checked * 100
```
