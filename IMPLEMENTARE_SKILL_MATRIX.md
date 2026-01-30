# Skill Matrix – Implementare (MVP) în proiectul `Matrix`

Acest MVP îți dă:
- **Matrice Angajați × Poziții** (filtru pe linie + căutare angajat)
- click pe celulă → **istoric evenimente** + **adăugare eveniment** (Start/Final/Validat/Aprobat etc.)
- status curent = **ultimul eveniment** din `dock_skill_matrix_event`

---

## 1) Fișiere adăugate

```
public/
  matrix.php
  employee.php
  events_api.php
  lookup_api.php

src/
  db.php
  repositories/
    LookupRepo.php
    EventsRepo.php
  services/
    SkillStatusService.php
```

`public/index.php` a fost actualizat cu buton către matrice.

---

## 2) Cum rulezi

1. Copiezi folderul `Matrix` în serverul tău PHP (ex: Apache / Nginx / IIS + PHP)
2. Asigură-te că extensia **pgsql** este activă în PHP.
3. Accesezi aplicația cu `user_id` în URL, exemplu:

```
/Matrix/public/index.php?user_id=12
```

Apoi click pe **Deschide Matricea**.

---

## 3) Ce tabele sunt folosite

Nomenclatoare:
- `doc_pozitii_skill_matrix(id, denumire, ...)`
- `nom_productie_linii(id, denumire, ...)`
- `nom_sal_personal_angajat(id, denumire / nume / prenume / nume_complet, ...)`

Definiția cerinței (obligatoriu pentru insert event):
- `doc_skill_matrix_det(id, pozitie_id, linie_id, ...)`

Event log:
- `dock_skill_matrix_event(...)`

> La INSERT, sistemul caută automat `skill_det_id` din `doc_skill_matrix_det` pe `(pozitie_id, linie_id)`.
> Dacă nu există, API-ul refuză salvarea (asta e intenționat, ca să nu ai evenimente fără requirement definit).

---

## 4) Status curent (logica cheie)

Statusul curent pe o celulă (angajat+poziție, pentru linia selectată) se calculează cu:

- “ultimul eveniment” după `facut_la` (și `id` ca tie-break)

SQL folosit (în `EventsRepo::latestStatusMapForLine`):

```sql
SELECT DISTINCT ON (e.angajat_id, e.pozitie_id)
  e.angajat_id, e.pozitie_id, e.status_nou, e.actiune, e.facut_la
FROM dock_skill_matrix_event e
WHERE e.linie_id = $1
ORDER BY e.angajat_id, e.pozitie_id, e.facut_la DESC, e.id DESC;
```

---

## 5) API-uri (pentru UI)

### `lookup_api.php`
- `?type=lines`
- `?type=positions`
- `?type=employees&q=...`

### `events_api.php`
- `GET` pentru timeline:
  - `?angajat_id=...&pozitie_id=...&linie_id=...`
- `POST` pentru adăugare event (cu CSRF din sesiune):
  - `angajat_id, pozitie_id, linie_id, actiune, status_nou, motiv, observatii`

---

## 6) Extensii rapide (următorii pași)

### Etapa 2 – “Requirements” vizibile în UI
- pagină “Setări Matrix”: definești/editezi `doc_skill_matrix_det`
- în matrice, celulele fără `skill_det_id` apar “N/A”

### Etapa 3 – Roluri / drepturi pe acțiuni
- allowlist acțiuni în funcție de rolul userului (`sys_users` → rol)

### Etapa 4 – PDF + semnătură
- `export_pdf.php`
- evenimente: `Generare_Pdf`, `Semnare`
- stocare PDF + link download

