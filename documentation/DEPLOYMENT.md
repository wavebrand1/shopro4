# Wdrożenie środowiska developerskiego

## Przepływ

```text
push do GitHub/main
→ webhook GitHub
→ integracja Git w Plesku pobiera origin/main
→ automatyczne wdrożenie do /httpdocs
→ skrypt bin/deploy-dev
→ zależności, migracje, assety i cache
```

Document root domeny `shopro4.orangestudio.pl` wskazuje na `/httpdocs/public`.
Repozytorium jest prywatne, a Plesk pobiera je przez dedykowany deploy key
z uprawnieniem tylko do odczytu.

## Polecenie wykonywane przez Plesk po wdrożeniu

W polu dodatkowych działań wdrożeniowych jest skonfigurowane jedno polecenie:

```bash
bash bin/deploy-dev
```

Skrypt wykonuje kolejno:

1. instalację zależności Composer,
2. migracje bazy danych,
3. instalację zasobów pakietów Symfony,
4. kompilację CSS i JavaScript przez AssetMapper,
5. czyszczenie cache Symfony.

Cache jest czyszczony po kompilacji, aby Symfony odczytało najnowszy manifest
assetów i nie podawało przeglądarce poprzednich adresów CSS.

## Kontrola

- GitHub Actions wykonuje walidację Composera, kontenera i testy.
- Plesk pokazuje ostatni pobrany commit i wynik wdrożenia.
- Niepowodzenie któregokolwiek polecenia przerywa skrypt z kodem błędu.
- Cała domena developerska jest chroniona uwierzytelnianiem HTTP w Plesku.
- Sekrety oraz `.env.local` pozostają poza repozytorium.
