        #mainContent { animation: fadeUp 0.22s ease both; }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(71,85,105,0.28); border-radius: 4px; }
        .dark ::-webkit-scrollbar-thumb { background: rgba(100,116,139,0.3); border-radius: 4px; }
        /*
         Native controls in content: date/select can show invisible values
         (dark UA / autofill / color-scheme). Force readable text + background.
         */
        #mainContent input:not([type="checkbox"]):not([type="radio"]):not([type="submit"]):not([type="button"]):not([type="hidden"]),
        #mainContent select,
        #mainContent textarea {
            color-scheme: light dark;
            color: rgb(15 23 42);
            background-color: rgb(255 255 255);
            border: 1px solid rgb(203 213 225);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.72);
        }
        .dark #mainContent input:not([type="checkbox"]):not([type="radio"]):not([type="submit"]):not([type="button"]):not([type="hidden"]),
        .dark #mainContent select,
        .dark #mainContent textarea {
            color: rgb(241 245 249);
            background-color: rgb(2 6 23);
            border-color: rgb(51 65 85);
            box-shadow: none;
        }
        #mainContent input::placeholder,
        #mainContent textarea::placeholder {
            opacity: 1;
            color: rgb(71 85 105);
        }
        .dark #mainContent input::placeholder,
        .dark #mainContent textarea::placeholder {
            color: rgb(148 163 184);
        }
        #mainContent input:-webkit-autofill,
        #mainContent input:-webkit-autofill:hover,
        #mainContent input:-webkit-autofill:focus {
            -webkit-text-fill-color: rgb(15 23 42);
            box-shadow: inset 0 0 0 1000px rgb(255 255 255);
            transition: background-color 99999s ease-out 0s;
        }
        .dark #mainContent input:-webkit-autofill,
        .dark #mainContent input:-webkit-autofill:hover,
        .dark #mainContent input:-webkit-autofill:focus {
            -webkit-text-fill-color: rgb(241 245 249);
            box-shadow: inset 0 0 0 1000px rgb(2 6 23);
            transition: background-color 99999s ease-out 0s;
        }
