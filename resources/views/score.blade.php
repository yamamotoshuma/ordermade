<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            スコア表作成
        </h2>
    </x-slot>    
    <div class="grid grid-cols-3 gap-4" id="iii">
  <h2 class="text-2xl font-bold">スコア表作成</h2>
  <div class="col-span-1">
    <div class="mb-4">
      <label for="memberCount" class="block text-sm font-medium text-gray-700">メンバー数</label>
      <input type="number" class="border border-gray-300 rounded-md w-full py-2 px-3 text-sm" id="memberCount" />
    </div>
  </div>
  <div class="col-span-1">
    <div class="mb-4">
      <label for="inningCount" class="block text-sm font-medium text-gray-700">イニング数</label>
      <input type="number" class="border border-gray-300 rounded-md w-full py-2 px-3 text-sm" id="inningCount" />
    </div>
  </div>
  <div class="col-span-1 flex items-end justify-end">
    <button type="button" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg text-lg" onclick="createTable()">表作成</button>
  </div>
  <div class="col-span-1 flex items-end justify-end">
    <button type="button" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg text-lg" onclick="printTable()">印刷</button>
  </div>
</div>
<div id="printarea" class="mt-8">
  <div id="table-container"></div>
  <div id="table-container2"></div>
</div>
<style>
    table {
        border-collapse: collapse;
    }

    table, th, td {
        border: 1px solid black;
    }

        table.table-bordered td,
        table.table-bordered th {
            border: 1px solid black;
        }
</style>
<script>
  function createTable() {
    const tableContainer = document.getElementById("table-container");
    const memberCount = parseInt(document.getElementById("memberCount").value);
    const inningCount = parseInt(document.getElementById("inningCount").value);
    const memberTable = document.createElement("table");
    memberTable.classList.add("table", "table-bordered", "table-striped", "mt-4" ,"w-full");

    for (let i = 0; i <= memberCount; i++) {
      const row = document.createElement("tr");

      for (let j = 0; j <= inningCount + 2; j++) {
        const cell = document.createElement("td");

        if (i === 0 && j !== 0) {
          let inningText = "";
          if (j === 1) {
            inningText = document.createTextNode("名前");
          } else if (j === inningCount + 2) {
            inningText = document.createTextNode("計");
          } else {
            inningText = document.createTextNode(`${j - 1}回`);
          }
          cell.appendChild(inningText);
          cell.classList.add("font-medium");
        } else if (j === 0 && i !== 0) {
          const memberText = document.createTextNode(`${i}`);
          cell.appendChild(memberText);
          cell.classList.add("font-medium");
        }

        row.appendChild(cell);
      }

      memberTable.appendChild(row);
    }

    tableContainer.innerHTML = "";
    tableContainer.appendChild(memberTable);

    const tableContainer2 = document.getElementById("table-container2");
    const memberTable2 = document.createElement("table");
    memberTable2.classList.add("table", "table-bordered", "table-striped", "mt-4" , "w-full");

    for (let i = 0; i <= 5; i++) {
      const row = document.createElement("tr");

      for (let j = 0; j <= 7; j++) {
        const cell = document.createElement("td");

        if (i === 0 && j !== 0) {
          let inningText = "";
          switch (j) {
            case 1:
              inningText = document.createTextNode("名前");
              break;
            case 2:
              inningText = document.createTextNode("イニング");
              break;
            case 3:
              inningText = document.createTextNode("被安打");
              break;
            case 4:
              inningText = document.createTextNode("三振");
              break;
            case 5:
              inningText = document.createTextNode("四死球");
              break;
            case 6:
              inningText = document.createTextNode("失点");
              break;
            case 7:
              inningText = document.createTextNode("自責点");
              break;
          }
          cell.appendChild(inningText);
          cell.classList.add("font-medium");
        } else if (j === 0 && i !== 0) {
          const memberText = document.createTextNode(`${i}`);
          cell.appendChild(memberText);
          cell.classList.add("font-medium");
        }

        row.appendChild(cell);
      }

      memberTable2.appendChild(row);
    }

    tableContainer2.innerHTML = "";
    tableContainer2.appendChild(memberTable2);
  }

  function printTable() {
    var area = document.getElementById("printarea").outerHTML;

    var head = "";
    var cmd = '<script>window.print();</' + 'script>';

    var links = document.getElementsByTagName("link");
    for (var i = 0; i < links.length; i++) {
      head = head + links[i].outerHTML;
    }

    var styles = document.getElementsByTagName("style");
    for (var i = 0; i < styles.length; i++) {
      head = head + styles[i].outerHTML;
    }

    var sub = window.open();
    sub.document.write("<html><head>" + head + "</head><body>" + area + cmd + "</body></html>");
    sub.document.close();
  }
</script>
</x-app-layout>