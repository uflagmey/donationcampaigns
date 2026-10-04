## 1. Was die Erweiterung tut

Spendenkampagnen hängt eine **Spendenkampagne an ein Thema** und zeigt über dem ersten Beitrag
dieses Themas eine Box mit dem Spendenziel, dem gesammelten Betrag, einem Fortschrittsbalken und
– wahlweise – der Anzahl der Spenden und den Namen der Spender.

Zwei Dinge sind vorab wichtig:

- **Es werden nur bestätigte Spenden erfasst.** Eine Spende ist Geld, das *bereits eingegangen* ist
  und das du von Hand einträgst. Die Erweiterung **wickelt keine Zahlungen ab** und nimmt nie
  Kontakt zu PayPal, einer Bank oder einem Zahlungsanbieter auf.
- **Die Spenden-Schaltfläche ist nur ein Link.** Jede Kampagne verweist damit auf eine Web-Adresse
  deiner Wahl (einen PayPal-Link, eine Seite mit Bankdaten, ein anderes Thema …). Wer darauf
  klickt, verlässt das Board; das eingegangene Geld bestätigst du anschließend selbst.

## 2. Installation und Aktivierung

1. Kopiere die Erweiterung nach `ext/uflagmey/donationcampaigns/` auf deinem Board.
2. Gehe zu **Administrationsbereich** → **Anpassen** → **Erweiterungen** → **Donation Campaigns** →
   **Aktivieren**.

Oder auf der Kommandozeile:

```
php bin/phpbbcli.php extension:enable uflagmey/donationcampaigns
php bin/phpbbcli.php cache:purge
```

Beim Aktivieren werden die Datenbanktabellen, die Einstellungen, die Berechtigungen und das
ACP-Menü angelegt. **Deaktivieren** blendet später alles aus, behält aber die Daten; **Löschen**
(Deinstallieren) entfernt alle Kampagnen und Spenden unwiderruflich.

## 3. Währung einstellen

Gehe zu **Administrationsbereich** → **Erweiterungen** → **Spendenkampagnen** → **Einstellungen**.

| Einstellung | Bedeutung |
|---|---|
| **Währung** | Dreistelliger Code, z. B. `EUR`, `USD`, `GBP` |
| **Währungssymbol** | Wird neben jedem Betrag angezeigt, z. B. `€` |
| **Dezimalstellen** | `2` für die meisten Währungen, `0` für Yen, `3` für Dinar |
| **Angezeigte Spender** | Wie viele Spendernamen die öffentliche Box nennt, bevor sie den Rest zusammenfasst |

> ⚠ **Stelle die Dezimalstellen ein, bevor du die erste Spende erfasst.** Beträge werden als ganze
> Zahlen der kleinsten Einheit gespeichert (250,00 € werden als `25000` gespeichert). Eine spätere
> Änderung dieser Einstellung **liest jeden gespeicherten Betrag neu ein**, statt ihn umzurechnen –
> ein Wert kann dadurch plötzlich zehn- oder hundertmal zu groß oder zu klein erscheinen. Die
> Erweiterung warnt dich und verlangt eine Bestätigung, wenn bereits Daten vorhanden sind.

## 4. Festlegen, wer Kampagnen verwalten darf

Die Erweiterung hat **drei Berechtigungen**. Administratoren haben nach der Installation bereits
vollen Zugriff; diesen Abschnitt brauchst du nur, um **anderen Personen** die Mithilfe zu erlauben.

| Berechtigung | Erlaubt dem Inhaber … |
|---|---|
| **Kann Spendenkampagnen verwalten** | Kampagnen erstellen, bearbeiten, aktivieren/deaktivieren und *leere* Kampagnen löschen |
| **Kann bestätigte Spenden verwalten** | Spenden erfassen, bearbeiten und löschen. ⚠ Dies gibt Zugriff auf Spendernamen, **private** Spenderidentitäten und die bestätigten Beträge – vergib sie nur an Personen, denen du diese personenbezogenen Daten anvertraust |
| *(Administrator-Zugriff)* | Alles Obige auf jedem Forum, dazu die Aufsichts- und Wartungsseiten im ACP. Wird den Rollen „Vollständiger Administrator" und „Standard-Administrator" automatisch erteilt |

Die beiden Verwaltungs-Berechtigungen gelten **pro Forum** und sind **unabhängig** – du kannst eine
ohne die andere vergeben.

**Wo du sie einstellst** (die beiden Verwaltungs-Berechtigungen):

1. Reiter **Administrationsbereich** → **Berechtigungen**.
2. Wähle unter **Forenbezogene Berechtigungen** den Punkt **Forenmoderatoren** (oder
   *Benutzer- / Gruppen-Berechtigungen für Foren*).
3. Wähle das **Forum** und dann den **Benutzer oder die Gruppe**.
4. Klicke auf **Erweiterte Berechtigungen** und öffne den Reiter **Spendenkampagnen**.
5. Setze die Berechtigungen auf **Ja** und klicke auf **Berechtigungen übernehmen**.

> **Hinweis:** Wer eine der Verwaltungs-Berechtigungen erhält, wird damit zum **Moderator dieses
> Forums** – phpBB führt ihn entsprechend auf. Das ist normales phpBB-Verhalten und kein Grund zur
> Sorge, aber gut zu wissen.

## 5. Eine Kampagne erstellen

Kampagnen werden **aus dem Thema heraus erstellt**, zu dem sie gehören – es gibt keine Themen-ID
einzutragen.

1. Öffne das Thema.
2. Klicke auf **Themenwerkzeuge** (das Schraubenschlüssel-Symbol) → **Spendenkampagne**.
3. Fülle das Formular aus:

| Feld | Hinweise |
|---|---|
| **Titel** | Die Überschrift der öffentlichen Box |
| **Beschreibung** | Optional. BBCode, Smilies und Links sind erlaubt |
| **Spendenziel** | Zum Beispiel `250,00` oder `250.00`. Muss größer als null sein |
| **Spendenlink** | Optional. Eine vollständige `http://`- oder `https://`-Adresse |
| **Linktext** | Der Text auf der Schaltfläche, z. B. *Über PayPal spenden*. Erforderlich, wenn ein Link gesetzt ist |
| **Spendernamen anzeigen** | Ob die öffentliche Box Spendernamen nennen darf |
| **Anzahl der Spenden anzeigen** | Ob die Box zeigt, wie viele Spenden es gibt |

4. Klicke auf **Absenden**, dann auf **Zurück zum Thema** – die Box erscheint nun über dem ersten
   Beitrag.

Eine Kampagne pro Thema. Derselbe Menüpunkt **Spendenkampagne** öffnet später die
**Verwaltungsseite** dieser Kampagne.

## 6. Eine bestehende Kampagne verwalten

Öffne das Thema → **Themenwerkzeuge** → **Spendenkampagne**. Die Verwaltungsseite zeigt den Status
der Kampagne und ihre Spenden, mit Schaltflächen für die Aktionen, die du ausführen darfst:

- **Kampagne bearbeiten** – Titel, Spendenziel, Link usw. ändern.
- **Deaktivieren / Aktivieren** – *Deaktivieren* blendet die Box aus dem Thema aus, behält aber
  jede Spende; *Aktivieren* holt sie zurück.
- **Löschen** – nur möglich, solange die Kampagne **keine** Spenden hat. Eine Kampagne mit Spenden
  muss stattdessen deaktiviert werden (ein Administrator kann sie im ACP endgültig löschen, wenn sie
  wirklich weg soll).

## 7. Eine Spende erfassen

**Erst, nachdem das Geld tatsächlich eingegangen ist und du es geprüft hast.**

1. Öffne das Thema → **Themenwerkzeuge** → **Spendenkampagne**.
2. Klicke auf **Bestätigte Spende erfassen** (wird Inhabern von *Kann bestätigte Spenden verwalten*
   angezeigt).
3. Trage den Beleg ein:

| Feld | Hinweise |
|---|---|
| **Betrag** | Der tatsächlich eingegangene Betrag, `50,00` oder `50.00` |
| **Eingegangen am** | Das Datum, an dem das **Geld eingegangen** ist, nicht das heutige |
| **Spender** | Der öffentlich anzuzeigende Name. Leer lassen, um die Spende als *Anonym* zu erfassen |
| **Spender öffentlich anzeigen** | Abwählen, um die Spende zu zählen, aber den Namen zu verbergen |

4. Klicke auf **Absenden** – die Kampagnensumme wird sofort neu berechnet.

Jede Spende in der Liste hat die Aktionen **Bearbeiten** und **Löschen**. Eine Spende mit verborgenem
Namen **zählt trotzdem** zur Summe und zur Spendenanzahl; nur der Name wird zurückgehalten und als
*Anonym* angezeigt.

> **Frage einen Spender, bevor du seinen Namen veröffentlichst.** Die Erweiterung kann nicht
> wissen, ob seine Einwilligung vorliegt.

## 8. Was deine Besucher sehen

Im Thema, über dem ersten Beitrag, zeigt die Box den Titel, die optionale Beschreibung, einen
Fortschrittsbalken, den gesammelten Betrag gegenüber dem Spendenziel und – je nach Einstellung der
Kampagne – die Spendenanzahl und die Liste der Spendernamen. Jede bestätigte Spende ist mit ihrem
Betrag enthalten; eine private Spende erscheint als *Anonym* mit ihrem Betrag, wird also nie
verborgen.

## 9. Verwaltung und Aufsicht (ACP)

Das ACP (**Administrationsbereich** → **Erweiterungen** → **Spendenkampagnen**) dient der **Aufsicht
und Wartung**, nicht der täglichen Erfassung:

- **Kampagnen** – eine schreibgeschützte Liste aller Kampagnen des Boards. Die Zeilen-Links öffnen
  die Kampagne in ihrem Thema.
- **Spenden** – eine schreibgeschützte Liste der Spenden einer Kampagne, mit einem Link, um sie im
  Thema zu verwalten.
- **Summe neu berechnen** – berechnet die gespeicherte Summe einer Kampagne aus ihren Spenden neu
  (jederzeit gefahrlos möglich; nützlich nach einem zurückgespielten Backup).
- **Löschen** – ein Administrator darf hier auch eine **nicht leere** Kampagne löschen (dabei werden
  ihre Spenden mit entfernt); die Bestätigung nennt die Kampagne und ihre Spendenanzahl.

**Wo Aktionen protokolliert werden:** Was aus einem Thema heraus geschieht, wird im
**Moderationsprotokoll** festgehalten (Moderatoren-Kontrollzentrum → Forenprotokolle), bezogen auf
Forum und Thema; was im ACP geschieht, wird im **Administrationsprotokoll** festgehalten
(Administrationsbereich → Wartung → Protokolle).

## 10. Fehlerbehebung

| Symptom | Prüfen |
|---|---|
| Die Box erscheint nicht im Thema | Ist die Kampagne **aktiviert**? Cache leeren |
| Die Summe stimmt nicht | **Summe neu berechnen** im ACP verwenden |
| Beträge sind um Faktor zehn oder hundert daneben | Die Einstellung **Dezimalstellen** wurde nach dem Erfassen von Daten geändert (siehe §3) |
| Ein Spendername erscheint, obwohl er nicht sollte | Beide Schalter prüfen: **Spendernamen anzeigen** der Kampagne **und Spender öffentlich anzeigen** dieser Spende |
| Der Menüpunkt **Spendenkampagne** fehlt in einem Thema | Der Benutzer hat auf diesem Forum keine der Verwaltungs-Berechtigungen (siehe §4) |

---

*Ausführlichere Informationen enthalten die mit der Erweiterung ausgelieferten Dokumente
`README.md`, `docs/ADMIN_GUIDE.md` und `docs/PRIVACY.md` (in englischer Sprache).*
