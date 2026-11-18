const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');
const ts = require('typescript');

function loadSource(file, dependencies = {}) {
  const filename = path.join(__dirname, '../src/forum', file);
  const { outputText } = ts.transpileModule(readFileSync(filename, 'utf8'), {
    compilerOptions: {
      module: ts.ModuleKind.CommonJS,
      target: ts.ScriptTarget.ES2020,
      jsx: ts.JsxEmit.React,
      jsxFactory: 'm',
      esModuleInterop: true,
    },
  });
  const module = { exports: {} };
  vm.runInNewContext(
    outputText,
    {
      module,
      exports: module.exports,
      require(name) {
        assert.ok(name in dependencies, `Unexpected dependency: ${name}`);
        return dependencies[name];
      },
    },
    { filename }
  );
  return module.exports;
}

const ranking = loadSource('utils/rankNominees.ts');
const rankNominees = ranking.default;
const nominee = (id, votes) => ({ id: () => String(id), voteCount: () => votes });

test('zero or unavailable counts do not produce winners or medal places', () => {
  const ranked = rankNominees([nominee(1, 0), undefined, nominee(2, null), nominee(3, undefined)]);
  assert.deepEqual(
    Array.from(ranked, (entry) => entry.rank),
    [null, null, null]
  );
  assert.equal(ranking.previewNominees(ranked).length, 3);
});

test('equal totals share competition ranks and do not mutate nominee order', () => {
  const input = [nominee(1, 3), nominee(2, 5), nominee(3, 5), nominee(4, 1)];
  const ranked = rankNominees(input);
  assert.deepEqual(
    Array.from(ranked, (entry) => [entry.nominee.id(), entry.rank]),
    [
      ['2', 1],
      ['3', 1],
      ['1', 3],
      ['4', 4],
    ]
  );
  assert.deepEqual(
    input.map((item) => item.id()),
    ['1', '2', '3', '4']
  );
});

test('the podium preview includes every nominee tied for third place', () => {
  const ranked = rankNominees([nominee(1, 8), nominee(2, 5), nominee(3, 2), nominee(4, 2), nominee(5, 1)]);
  assert.deepEqual(
    Array.from(ranking.previewNominees(ranked), (entry) => entry.rank),
    [1, 2, 3, 3]
  );
});

test('zero-count nominees do not receive medals alongside positive totals', () => {
  const ranked = rankNominees([nominee(1, 4), nominee(2, 0), nominee(3, 0)]);
  assert.deepEqual(
    Array.from(ranked, (entry) => entry.rank),
    [1, null, null]
  );
  assert.equal(ranking.previewNominees(ranked).length, 1);
});

function predictions(counts, selections) {
  const nominees = counts.map((count, index) => nominee(index + 1, count));
  const votes = selections.map(([nomineeId, userId = 1]) => ({
    nomineeId: () => nomineeId,
    userId: () => userId,
    categoryId: () => 1,
    relationshipId: () => undefined,
  }));
  const app = { session: { user: { id: () => '1' } }, store: { all: () => votes } };
  const Summary = loadSource('components/PredictionSummary.tsx', {
    'flarum/forum/app': app,
    'flarum/common/Component': class {},
    '../utils/rankNominees': ranking,
  }).default;
  return new Summary().getPredictionResults(null, [{ id: () => '1', nominees: () => nominees }])[0];
}

test('voting for either tied winner is a correct prediction', () => {
  const result = predictions([5, 5, 1], [[2]]);
  assert.equal(result.winners.length, 2);
  assert.equal(result.isCorrect, true);
});

test('multi-vote predictions recognize a winner even after a losing ballot', () => {
  assert.equal(predictions([5, 5, 1], [[3], [2]]).isCorrect, true);
});

test("another voter cannot make the current user's prediction correct", () => {
  const result = predictions([5, 5, 1], [[3], [2, 2]]);
  assert.equal(result.isCorrect, false);
});

test('a zero-count category has no correct winner prediction', () => {
  const result = predictions([0, 0], [[1]]);
  assert.equal(result.winners.length, 0);
  assert.equal(result.isCorrect, false);
});
