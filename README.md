# Fronius Modbus Register Maps → Edomi JSON

Tools for converting official Fronius Modbus Register Map XLSX files into the
JSON format used by the Edomi Modbus TCP Connector (LBS 19002762).

---

## Requirements

- Python 3.8+
- openpyxl: `pip install openpyxl`

---

## Obtaining XLSX Files

Download the latest register maps from the official Fronius download center:

> https://www.fronius.com/en/download-center?searchword=modbus

Place the files in `extracted_xls/`. The following maps are already included:

| File | Device | Mode |
|---|---|---|
| `Gen24_Primo_Symo_Inverter_Register_Map_Float_ROW.xlsx` | GEN24 Primo/Symo | Float, ROW |
| `Gen24_Primo_Symo_Inverter_Register_Map_Float_storage_ROW.xlsx` | GEN24 Primo/Symo + Storage | Float, ROW |
| `Gen24_Primo_Symo_Inverter_Register_Map_Float_US.xlsx` | GEN24 Primo/Symo | Float, US |
| `Gen24_Primo_Symo_Inverter_Register_Map_Float_storage_US.xlsx` | GEN24 Primo/Symo + Storage | Float, US |
| `Gen24_Primo_Symo_Inverter_Register_Map_Int&SF_ROW.xlsx` | GEN24 Primo/Symo | Int+SF, ROW |
| `Gen24_Primo_Symo_Inverter_Register_Map_Int&SF_storage_ROW.xlsx` | GEN24 Primo/Symo + Storage | Int+SF, ROW |
| `Gen24_Primo_Symo_Inverter_Register_Map_Int&SF_US.xlsx` | GEN24 Primo/Symo | Int+SF, US |
| `Gen24_Primo_Symo_Inverter_Register_Map_Int&SF_storage_US.xlsx` | GEN24 Primo/Symo + Storage | Int+SF, US |
| `Smart_Meter_Register_Map_Float.xlsx` | Smart Meter TS 65A | Float |
| `Smart_Meter_Register_Map_Int&SF.xlsx` | Smart Meter TS 65A | Int+SF |
| `Tauro_Inverter_Register_Map_Float_ROW.xlsx` | Tauro | Float, ROW |
| `Tauro_Inverter_Register_Map_Int&SF_ROW.xlsx` | Tauro | Int+SF, ROW |
| `Tauro_ECO_Inverter_Register_Map_Float_ROW.xlsx` | Tauro ECO | Float, ROW |
| `Tauro_ECO_Inverter_Register_Map_Int&SF_ROW.xlsx` | Tauro ECO | Int+SF, ROW |
| `Verto_Inverter_Register_Map_3MPPT_Float_ROW.xlsx` | Verto 3-MPPT | Float, ROW |
| `Verto_Inverter_Register_Map_3MPPT_Float_storage_ROW.xlsx` | Verto 3-MPPT + Storage | Float, ROW |
| `Verto_Inverter_Register_Map_3MPPT_Int&SF_ROW.xlsx` | Verto 3-MPPT | Int+SF, ROW |
| `Verto_Inverter_Register_Map_3MPPT_Int&SF_storage_ROW.xlsx` | Verto 3-MPPT + Storage | Int+SF, ROW |
| `Verto_Inverter_Register_Map_4MPPT_Float_ROW.xlsx` | Verto 4-MPPT | Float, ROW |
| `Verto_Inverter_Register_Map_4MPPT_Int&SF_ROW.xlsx` | Verto 4-MPPT | Int+SF, ROW |

**ROW** = Rest of World (Europe), **US** = USA/Canada  
**Float** = Model 11x (float32 values), **Int+SF** = Model 10x/103 (int16 + Scale Factors)

To determine which mode your device uses, read register 40070 (`ID`):
- `101–103` → Int+SF
- `111–113` → Float

---

## Generating JSON Files

```bash
cd /home/sipiyou/edomi/fronius/git

# Process all XLSX files in extracted_xls/ (one JSON per XLSX)
python3 xls2json.py

# Process specific files only
python3 xls2json.py "Gen24_Primo_Symo_Inverter_Register_Map_Int&SF_storage_ROW.xlsx"

# Write output to a different directory
python3 xls2json.py --outdir /tmp/json_out
```

Output JSON files are placed in the same directory as `xls2json.py`,
named after the source XLSX file (`.json` extension).

**IP address, port and Unit ID are intentionally not stored in the JSON.**
These connection parameters are entered as required fields during import
in the Edomi admin backend (see Import section below).

---

## JSON Format

Each JSON file contains an array with one device object:

```json
[
  {
    "device": "Gen24_Primo_Symo_Inverter_Int+SF_storage_ROW",
    "protocol": "TCP",
    "elements": [
      {
        "firstID": 40070,
        "group": "inverter_10x",
        "elements": {
          "2": {
            "start": 40070,
            "size": 1,
            "rw": "R",
            "function": "0x03",
            "name": "ID",
            "desc": "Well-known value. Uniquely identifies this as a sunspec model inverter (10x)",
            "type": "uint16",
            "unit": "",
            "scaleFactor": "",
            "range": "101, 103",
            "notsupported": 0
          }
        }
      }
    ]
  }
]
```

### Fields

**Device:**

| Field | Description |
|---|---|
| `device` | Device name (derived from XLSX filename) |
| `protocol` | Always `"TCP"` |
| `elements` | Array of register groups |

**Group:**

| Field | Description |
|---|---|
| `firstID` | Absolute Modbus start address of the block (auto-detected from Complete Map sheet) |
| `group` | Group name (derived from XLSX sheet name, e.g. `inverter_10x`) |
| `elements` | Object with numeric string keys (`"2"`, `"3"`, ...) |

**Register:**

| Field | Type | Description |
|---|---|---|
| `start` | int | Absolute Modbus address (1-indexed, SunSpec convention) |
| `size` | int | Number of 16-bit registers |
| `rw` | string | `"R"`, `"W"` or `"RW"` |
| `function` | string | Modbus function code (`"0x03"`) |
| `name` | string | Short name (e.g. `"W"`, `"V_SF"`) |
| `desc` | string | Description |
| `type` | string | Data type (see types below) |
| `unit` | string | Unit (e.g. `"W"`, `"V"`, `"A"`) |
| `scaleFactor` | string | Name of the associated SF register (e.g. `"W_SF"`) or empty |
| `range` | string | Value range as documented by Fronius |
| `notsupported` | int | `1` = not implemented by device, `0` = normal |

### Data Types

| Type | Description |
|---|---|
| `uint16` | Unsigned 16-bit integer |
| `int16` | Signed 16-bit integer |
| `uint32` | Unsigned 32-bit integer (2 registers) |
| `int32` | Signed 32-bit integer (2 registers) |
| `float32` | IEEE 754 float (2 registers, model 11x) |
| `acc64` | Accumulated 64-bit value (4 registers) |
| `string32` | Null-terminated string (up to 32 bytes) |
| `enum16` | Enumeration value (uint16) |
| `bitfield16` | Bit field (uint16) |
| `bitfield32` | Bit field (uint32) |
| `sunssf` | SunSpec Scale Factor (int16, exponent for 10^x) |

### Scale Factors (sunssf)

Registers of type `sunssf` contain an exponent. The actual value of a measurement
register is calculated as:

```
value = raw_value × 10^(SF value)
```

Example: `W = 1500`, `W_SF = -1` → `150.0 W`

The SF value `-32768` (`0x8000`) means "not implemented" and is ignored.
Valid SF values are in the range `-10` to `+10`.

---

## Importing into Edomi

1. Generate the JSON file (see above)
2. Open the Edomi Modbus Admin: `http://<edomi-ip>/Modbus/modbus_admin.php`
3. Click **JSON importieren**
4. Select the JSON file
5. Enter the connection parameters:
   - **IP address** of the device (e.g. `192.168.1.180`)
   - **Port** (default: `502`)
   - **Unit ID** (Modbus device address; Fronius GEN24 inverter = `1`, Smart Meter = `4`)
   - **Byte order** (default: Big-Endian / SunSpec)
6. Click **Importieren**

Re-importing an existing device preserves all existing KO assignments.
Connection parameters are updated.

---

## Directory Structure

```
git/
├── xls2json.py                  # XLSX → JSON converter
├── README.md                    # This file
├── extracted_xls/               # XLSX source files
│   ├── Gen24_Primo_Symo_*.xlsx
│   ├── Smart_Meter_*.xlsx
│   ├── Tauro_*.xlsx
│   └── Verto_*.xlsx
├── Gen24_Primo_Symo_*.json      # Generated JSON files
├── Smart_Meter_*.json
├── Tauro_*.json
└── Verto_*.json
```

---
