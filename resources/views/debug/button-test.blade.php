<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Button Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Force text visibility */
        .force-text {
            color: #dc2626 !important;
            font-size: 14px !important;
            font-weight: 500 !important;
            display: inline-block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        .force-white-text {
            color: white !important;
            font-size: 14px !important;
            font-weight: 500 !important;
            display: inline-block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        /* Override any potential CSS that hides text */
        button * {
            color: inherit !important;
        }
    </style>
</head>
<body class="p-8 bg-gray-100">
    <h1 class="text-2xl font-bold mb-6">Button Text Visibility Test</h1>
    
    <div class="space-y-4">
        <!-- Test 1: Basic red button -->
        <div>
            <h3 class="text-lg font-semibold mb-2">Test 1: Basic Red Button</h3>
            <button class="px-4 py-2 bg-red-600 text-white rounded">
                Basic Red Button
            </button>
        </div>
        
        <!-- Test 2: Outline red button -->
        <div>
            <h3 class="text-lg font-semibold mb-2">Test 2: Outline Red Button</h3>
            <button class="px-4 py-2 border-2 border-red-600 text-red-600 bg-white rounded">
                Outline Red Button
            </button>
        </div>
        
        <!-- Test 3: Force styled button -->
        <div>
            <h3 class="text-lg font-semibold mb-2">Test 3: Force Styled Button</h3>
            <button 
                class="px-4 py-2 border-2 border-red-600 bg-white rounded"
                style="color: #dc2626 !important; border-color: #dc2626 !important;"
            >
                <span class="force-text">Force Styled Text</span>
            </button>
        </div>
        
        <!-- Test 4: Exact copy of problematic button -->
        <div>
            <h3 class="text-lg font-semibold mb-2">Test 4: Exact Copy of Problematic Button</h3>
            <button
                type="button"
                class="inline-flex items-center px-3 py-1.5 border-2 border-red-600 text-sm font-medium rounded-md bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed"
                style="color: #dc2626 !important; border-color: #dc2626 !important; background-color: white !important;"
            >
                <svg class="w-4 h-4 mr-1" style="color: #dc2626;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span style="color: #dc2626 !important; font-size: 14px; font-weight: 500;">
                    Tolak Bukti
                </span>
            </button>
        </div>
        
        <!-- Test 5: Alternative approach -->
        <div>
            <h3 class="text-lg font-semibold mb-2">Test 5: Alternative Approach</h3>
            <div 
                class="inline-flex items-center px-3 py-1.5 border-2 border-red-600 text-sm font-medium rounded-md bg-white hover:bg-red-50 cursor-pointer"
                style="color: #dc2626 !important;"
            >
                <svg class="w-4 h-4 mr-1" fill="none" stroke="#dc2626" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span style="color: #dc2626 !important;">Tolak Bukti (DIV)</span>
            </div>
        </div>
        
        <!-- Test 6: Red background button -->
        <div>
            <h3 class="text-lg font-semibold mb-2">Test 6: Red Background Button</h3>
            <button 
                class="px-4 py-2 bg-red-600 text-white rounded"
                style="background-color: #dc2626 !important; color: white !important;"
            >
                <span class="force-white-text">Red Background Button</span>
            </button>
        </div>
    </div>
    
    <div class="mt-8 p-4 bg-yellow-100 border border-yellow-400 rounded">
        <h3 class="font-semibold">Instructions:</h3>
        <p>Check which buttons show text properly. If any button doesn't show text, there might be CSS conflicts in the main application.</p>
    </div>
</body>
</html>
